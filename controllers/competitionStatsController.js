const axios = require('axios'); // to fetch your PHP endpoint

exports.renderPage = (req, res) => {
  res.render('competition-stats'); // EJS page
};

exports.getData = async (req, res) => {
  try {
    const { competition, season, phase, phasedetail, level } = req.query;

    // Build query params for PHP endpoint
    const params = new URLSearchParams();
    if (competition) params.append('competition', competition);
    if (season) params.append('season', season);
    if (phase) params.append('phase', phase);
    if (phasedetail) params.append('phasedetail', phasedetail);
    if (level)       params.append('level', level);

    const phpUrl = `https://beta.kenyschulz.com/referee/api/getcompetitionstats.php?${params.toString()}`;

    const response = await axios.get(phpUrl);
    res.json(response.data);

  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Failed to fetch competition stats' });
  }
};