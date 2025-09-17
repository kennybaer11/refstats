const fetch = require("node-fetch");

exports.getFixtures = async (req, res) => {
  try {
    // optional query params
    const query = new URLSearchParams(req.query).toString();
    const apiUrl = `https://beta.kenyschulz.com/referee/api/getfixtures.php?${query}`;

    const response = await fetch(apiUrl);
    const data = await response.json();

    if (!data.success) {
      return res.status(500).send("Error loading fixtures");
    }

    const fixtures = data.fixtures || [];

    res.render("fixtures", { fixtures });
  } catch (err) {
    console.error("Error fetching fixtures:", err);
    res.status(500).send("Error loading fixtures");
  }
};
