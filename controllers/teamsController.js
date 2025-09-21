const fetch = require("node-fetch");
const axios = require("axios"); // add this

// list of teams
exports.getTeams = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getteams.php");
    const teams = await response.json();
    res.render("teams", { teams });
  } catch (err) {
    console.error("Error fetching teams:", err);
    res.status(500).send("Error loading teams");
  }
};

// team detail stats
exports.showTeam = async (req, res) => {
  const teamId = req.params.id;
  if (!teamId) return res.status(400).send("Missing team ID");

  const statsApiUrl = `https://beta.kenyschulz.com/referee/api/getteamstats.php?team_id=${teamId}`;
  const newsApiUrl = `https://beta.kenyschulz.com/referee/api/getnews.php`;
  const matchesApiUrl = `https://beta.kenyschulz.com/referee/api/getmatches.php?team_id=${teamId}`;

  try {
    const [statsRes, newsRes, matchesRes] = await Promise.all([
      axios.get(statsApiUrl),
      axios.get(newsApiUrl),
      axios.get(matchesApiUrl)
    ]);

    const teamStats = statsRes.data;
    const allNews = newsRes.data;
    const teamMatches = matchesRes.data || [];

    // Filter news related to this team
    const relatedNews = allNews.filter(post =>
      post.tags && post.tags.some(tag => tag.entity_type === 'team' && tag.entity_id === teamId)
    );

    // Merge everything into a single object for EJS
    const team = {
      id: teamStats.team_id,
      name: teamStats.team_name,
      statsDetail: teamStats.stats_phasedetail || [],
      statsPhase: teamStats.stats_phase || [],
      statsSeason: teamStats.stats_season || [],
      overall: teamStats.overall || {},
      relatedNews,
      matches: teamMatches
    };

    res.render("team-detail", { team });
  } catch (err) {
    console.error("Error fetching team data:", err.message);
    res.status(500).send("Error loading team data");
  }
};

