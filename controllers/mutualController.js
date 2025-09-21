const fetch = require("node-fetch");
const axios = require("axios");

// list of all mutuals
exports.getMutuals = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getmutual.php");
    const mutuals = await response.json();

    res.render("mutuals", { mutuals });
  } catch (err) {
    console.error("Error fetching mutuals:", err);
    res.status(500).send("Error loading mutuals");
  }
};

// Mutual detail: stats, matches, and related news
exports.getMutualStats = async (req, res) => {
  const { mutualId } = req.params;

  if (!mutualId) {
    return res.status(400).send("Missing mutual ID");
  }

  try {
    const statsUrl = `https://beta.kenyschulz.com/referee/api/getmutualstats.php?mutual_id=${encodeURIComponent(mutualId)}`;
    const matchesUrl = `https://beta.kenyschulz.com/referee/api/getmatches.php?mutual_id=${encodeURIComponent(mutualId)}`;
    const newsUrl = `https://beta.kenyschulz.com/referee/api/getnews.php`;

    // Fetch all in parallel
    const [statsRes, matchesRes, newsRes] = await Promise.all([
      axios.get(statsUrl),
      axios.get(matchesUrl),
      fetch(newsUrl)
    ]);

    const mutualStats = statsRes.data;
    const mutualMatches = matchesRes.data;
    const allNews = await newsRes.json();

    // Filter news related to this mutual
    const relatedNews = allNews.filter(
      post =>
        post.tags &&
        post.tags.some(
          tag => tag.entity_type === "mutual" && tag.entity_id === mutualId
        )
    );

    res.render("mutual-detail", {
      mutualStats,
      mutualMatches,
      relatedNews
    });
  } catch (error) {
    console.error("Error fetching mutual detail:", error.message);
    res.status(500).send("Failed to load mutual detail");
  }
};

