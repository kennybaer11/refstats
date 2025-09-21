const fetch = require("node-fetch");

exports.getPhases = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getphase.php");
    const phases = await response.json();

    res.render("phases", { phases });
  } catch (err) {
    console.error("Error fetching phases:", err);
    res.status(500).send("Error loading phases");
  }
};