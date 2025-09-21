const fetch = require("node-fetch");

exports.getReferees = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getreferees.php");
    const referees = await response.json();
    res.render("referees", { referees });
  } catch (err) {
    console.error("Error fetching referees:", err);
    res.status(500).send("Error loading referees");
  }
};

// show referee detail with stats, matches, comments, and related news
exports.showReferee = async (req, res) => {
  const refereeId = req.params.id;

  const statsApiUrl = `https://beta.kenyschulz.com/referee/api/getsinglerefereestats.php?referee_id=${encodeURIComponent(refereeId)}`;
  const matchesApiUrl = `https://beta.kenyschulz.com/referee/api/getsinglerefereematches.php?referee_id=${encodeURIComponent(refereeId)}`;
  const commentsApiUrl = `https://beta.kenyschulz.com/referee/api/getrefereecomments.php?referee_id=${encodeURIComponent(refereeId)}`;
  const newsApiUrl = `https://beta.kenyschulz.com/referee/api/getnews.php`;

  try {
    // Fetch all APIs in parallel
    const [statsRes, matchesRes, commentsRes, newsRes] = await Promise.all([
      fetch(statsApiUrl),
      fetch(matchesApiUrl),
      fetch(commentsApiUrl),
      fetch(newsApiUrl)
    ]);

    const refereeStats = await statsRes.json();
    const refereeMatches = await matchesRes.json();
    const refereeComments = await commentsRes.json();
    const allNews = await newsRes.json();

    // Filter news related to this referee
    const relatedNews = allNews.filter(post => 
      post.tags && post.tags.some(tag => tag.entity_type === 'referee' && tag.entity_id === refereeId)
    );

    // Merge data into one object
    const referee = {
      ...refereeStats,
      matches: refereeMatches.matches || [],
      comments: refereeComments || [],
      relatedNews
    };

    res.render("referee-detail", { referee });
  } catch (err) {
    console.error("Error fetching referee data:", err);
    res.status(500).send("Error loading referee data");
  }
};
