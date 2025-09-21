const express = require('express');
const router = express.Router();
const axios = require('axios');

router.get('/referee/:id', async (req, res) => {
  const refId = req.params.id;

  try {
    // Fetch all relevant data
    const [refereeRes, matchesRes, statsRes, pairsRes, competitionsRes, seasonsRes, commentsRes] = await Promise.all([
      axios.get(`http://beta.kenyschulz.com/referee/api/get-table.php?table=refereessql`),
      axios.get(`http://beta.kenyschulz.com/referee/api/get-table.php?table=matchesmysql`),
      axios.get(`http://beta.kenyschulz.com/referee/api/get-table.php?table=matchesstatssql`),
      axios.get(`http://beta.kenyschulz.com/referee/api/get-table.php?table=refereepairsql`),
      axios.get(`http://beta.kenyschulz.com/referee/api/get-table.php?table=competitionsql`),
      axios.get(`http://beta.kenyschulz.com/referee/api/get-table.php?table=seasonssql`),
      axios.get(`http://beta.kenyschulz.com/referee/api/get-table.php?table=referee_comments&public=1`)
    ]);

    const referee = refereeRes.data.find(r => r.referee_id == refId);
    if (!referee) return res.status(404).send('Referee not found');

    const matches = matchesRes.data;
    const stats = statsRes.data;
    const pairs = pairsRes.data;
    const competitions = competitionsRes.data;
    const seasonsList = seasonsRes.data;
    const comments = commentsRes.data.filter(c => c.referee_id == refId);

    // Create lookup maps
    const competitionMap = {};
    competitions.forEach(c => competitionMap[c.competition_id] = c.competition_name);

    const seasonMap = {};
    seasonsList.forEach(s => seasonMap[s.season_id] = s.season_name);

    // Filter matches where referee appears in ref1_id or ref2_id
    const refereeMatches = matches.filter(m => m.ref1_id == refId || m.ref2_id == refId);

    // Aggregate personal stats
    const personalStatsMap = {};

    refereeMatches.forEach(match => {
      const key = `${match.competition_id}_${match.season_id}`;
      if (!personalStatsMap[key]) {
        personalStatsMap[key] = {
          league: competitionMap[match.competition_id] || match.competition_id,
          season: seasonMap[match.season_id] || match.season_id,
          matches: 0,
          total_2min: 0,
          total_5min: 0,
          total_penalties: 0
        };
      }
      const stat = stats.find(s => s.match_id === match.match_id);
      personalStatsMap[key].matches += 1;
      if (stat) {
        personalStatsMap[key].total_2min += stat.total_2min || 0;
        personalStatsMap[key].total_5min += stat.total_5min || 0;
        personalStatsMap[key].total_penalties += stat.total_penalties || 0;
      }
    });

    const personalStats = Object.values(personalStatsMap).map(s => ({
      ...s,
      avg_2min: (s.total_2min / s.matches).toFixed(2),
      avg_5min: (s.total_5min / s.matches).toFixed(2)
    }));

    // Aggregate pair stats (pairs where this referee is in ref1_id or ref2_id)
    const pairStatsMap = {};

    refereeMatches.forEach(match => {
      if (!match.pair_id) return;
      const pairInfo = pairs.find(p => p.pair_id == match.pair_id);
      if (!pairInfo) return;

      const key = `${match.pair_id}_${match.competition_id}_${match.season_id}`;
      if (!pairStatsMap[key]) {
        pairStatsMap[key] = {
          pair_name: pairInfo.pair_name || `Pair ${match.pair_id}`,
          league: competitionMap[match.competition_id] || match.competition_id,
          season: seasonMap[match.season_id] || match.season_id,
          matches: 0,
          total_2min: 0,
          total_5min: 0,
          total_penalties: 0
        };
      }
      const stat = stats.find(s => s.match_id === match.match_id);
      pairStatsMap[key].matches += 1;
      if (stat) {
        pairStatsMap[key].total_2min += stat.total_2min || 0;
        pairStatsMap[key].total_5min += stat.total_5min || 0;
        pairStatsMap[key].total_penalties += stat.total_penalties || 0;
      }
    });

    const pairStats = Object.values(pairStatsMap).map(s => ({
      ...s,
      avg_2min: (s.total_2min / s.matches).toFixed(2),
      avg_5min: (s.total_5min / s.matches).toFixed(2)
    }));

    // Sort newest comments first
    comments.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    res.render('referee-profile', {
      referee,
      personalStats,
      pairStats,
      comments
    });

  } catch (err) {
    console.error(err);
    res.status(500).send('Server error');
  }
});

// POST comment
router.post('/referee/:id/comment', async (req, res) => {
  try {
    const refId = req.params.id;
    const { user_name, comment, rating } = req.body;

    await axios.post('http://beta.kenyschulz.com/referee/api/save-comment.php', {
      referee_id: refId,
      user_name,
      comment,
      rating
    });

    res.redirect(`/referee/${refId}`);
  } catch (err) {
    console.error(err);
    res.status(500).send('Error saving comment');
  }
});

module.exports = router;