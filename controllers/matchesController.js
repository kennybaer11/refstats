const fetch = require("node-fetch");

exports.getMatches = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getmatches.php");
    const matches = await response.json();

    res.render("matches", { matches });
  } catch (err) {
    console.error("Error fetching matches:", err);
    res.status(500).send("Error loading matches");
  }
};