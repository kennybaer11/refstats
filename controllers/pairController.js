const fetch = require("node-fetch");

exports.getPairs = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getpairs.php");
    const pairs = await response.json();

    res.render("pairs", { pairs });
  } catch (err) {
    console.error("Error fetching pairs:", err);
    res.status(500).send("Error loading pairs");
  }
};

// show referee pair matches
exports.showPair = async (req, res) => {
  const pairId = req.params.id;

  const statsApiUrl = `https://beta.kenyschulz.com/referee/api/getpairrefereestats.php?pair_id=${encodeURIComponent(pairId)}`;
  const matchesApiUrl = `https://beta.kenyschulz.com/referee/api/getpairrefereematches.php?pair_id=${encodeURIComponent(pairId)}`;
  const newsApiUrl = `https://beta.kenyschulz.com/referee/api/getnews.php`;

  try {
    const [statsRes, matchesRes, newsRes] = await Promise.all([
      fetch(statsApiUrl),
      fetch(matchesApiUrl),
      fetch(newsApiUrl)
    ]);

    const pairStats = await statsRes.json();
    const pairMatches = await matchesRes.json();
    const allNews = await newsRes.json();

 // Filter news related to this referee
    const relatedNews = allNews.filter(post => 
      post.tags && post.tags.some(tag => tag.entity_type === 'referee_pair' && tag.entity_id === pairId)
    );

    // Merge matches into stats object
    const pair = {
      ...pairStats,
      matches: pairMatches.matches || [],
      relatedNews
    };

    res.render("pair-detail", { pair });
  } catch (err) {
    console.error("Error fetching referee pair data:", err);
    res.status(500).send("Error loading referee pair data");
  }
};
