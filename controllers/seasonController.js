const fetch = require("node-fetch");

exports.getSeasons = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getseasons.php");
    const seasons = await response.json();

    res.render("seasons", { seasons });
  } catch (err) {
    console.error("Error fetching seasons:", err);
    res.status(500).send("Error loading seasons");
  }
};