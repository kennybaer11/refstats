const fetch = require("node-fetch");

exports.getPhasedetail = async (req, res) => {
  try {
    const response = await fetch("https://beta.kenyschulz.com/referee/api/getphasedetail.php");
    const phasedetail = await response.json();

    res.render("phasedetail", { phasedetail });
  } catch (err) {
    console.error("Error fetching phasedetail:", err);
    res.status(500).send("Error loading phasedetail");
  }
};