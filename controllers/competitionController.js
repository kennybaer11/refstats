const fetch = require("node-fetch");
const axios = require("axios");

// All competitions list
exports.getCompetitions = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getcompetitions.php");
    const competitions = await response.json();

    res.render("competitions", { competitions });
  } catch (err) {
    console.error("Error fetching competitions:", err);
    res.status(500).send("Error loading competitions");
  }
};



// Single competition detail (stats + matches + related news)
exports.getCompetitionById = async (req, res) => {
  const id = req.params.id;

  try {
    // Fetch stats
    const statsResponse = await fetch(
      `https://beta.kenyschulz.com/referee/api/getcompetitionstats.php?competition=${id}&level=season`
    );
    const statsData = await statsResponse.json();

    // Fetch matches
    const matchesResponse = await axios.get(
      `https://beta.kenyschulz.com/referee/api/getcompetitionmatches.php?competition_id=${id}`
    );
    const matchesData = matchesResponse.data;

    // Fetch all news
    const newsResponse = await fetch('https://beta.kenyschulz.com/referee/api/getnews.php');
    const allNews = await newsResponse.json();

    // Filter news related to this competition
    const relatedNews = allNews.filter(post =>
      post.tags && post.tags.some(tag => tag.entity_type === 'competition' && tag.entity_id === id)
    );

    // Extract unique filter options
    const seasons = [...new Map(matchesData.matches.map(m => [m.season_id, { id: m.season_id, name: m.season_name }])).values()];
    const phases = [...new Map(matchesData.matches.map(m => [m.phase_id || 'all', { id: m.phase_id || 'all', name: m.phase_name || 'All Phases' }])).values()];
    const phasedetails = [...new Map(matchesData.matches.map(m => [m.phasedetail_id || 'all', { id: m.phasedetail_id || 'all', name: m.phasedetail_name || 'All' }])).values()];

    res.render("competition-detail", {
      competition: statsData.stats[0] || {},
      filters: statsData.filters || {},
      stats: statsData.stats || [],
      matches: matchesData.matches || [],
      competitionId: id,
      competitionName: matchesData.competition_name || statsData.stats[0]?.competition_name || '',
      seasonOptions: seasons,
      phaseOptions: phases,
      phasedetailOptions: phasedetails,
      relatedNews
    });

  } catch (err) {
    console.error("Error fetching competition detail:", err);
    res.status(500).send("Error loading competition detail");
  }
};
