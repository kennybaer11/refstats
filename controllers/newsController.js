const axios = require('axios');

exports.getNews = async (req, res) => {
  const response = await axios.get('https://beta.kenyschulz.com/referee/api/getnews.php');
  const news = response.data;
  res.render('news', { news });
};

exports.getNewsDetail = async (req, res) => {
  const newsId = req.params.id;
  try {
    const response = await axios.get("https://beta.kenyschulz.com/referee/api/getnews.php");
    const news = response.data;
    const post = news.find(n => n.id == newsId);

    if (!post) return res.status(404).send("News not found");

    // Fetch tags for this news
    const tagsRes = await axios.get(`https://beta.kenyschulz.com/referee/api/getnewstags.php?news_id=${newsId}`);
    const tags = tagsRes.data || [];

    res.render("news-detail", { post, tags });
  } catch (err) {
    console.error("Error loading news detail:", err);
    res.status(500).send("Error loading news detail");
  }
};



exports.getAddNewsPage = async (req, res) => {
  try {
    // Fetch all tags
    const tagsRes = await fetch('https://beta.kenyschulz.com/referee/api/getnewstags.php');
    const tags = await tagsRes.json();

    // Render admin add news page with tags
    res.render('admin-add-news', { tags });
  } catch (err) {
    console.error(err);
    res.status(500).send('Failed to load add news page');
  }
};

