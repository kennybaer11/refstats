// controllers/homesearchController.js
const axios = require('axios');

exports.search = async (req, res) => {
  try {
    const q = req.query.q;
    if (!q) return res.json({ results: [] });

    const phpRes = await axios.get(`https://beta.kenyschulz.com/referee/api/search.php?q=${encodeURIComponent(q)}`);
    const results = phpRes.data || []; // should be array of results
    res.json({ results });
  } catch (err) {
    console.error('Search error:', err);
    res.status(500).json({ results: [] });
  }
};