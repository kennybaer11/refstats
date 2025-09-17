const express = require("express");
const router = express.Router();
const matchController = require("../controllers/matchesController");
const axios = require("axios"); // npm install axios

// GET /phases
router.get("/", matchController.getMatches);

// Matchdetails Backend route start
router.get("/:id", async (req, res) => {
  const matchId = req.params.id;

  try {
    // 1. Load match
    const response = await axios.get(
      `https://beta.kenyschulz.com/referee/api/getmatch.php?fl=1&match_id=${matchId}`
    );
    const match = response.data;

    if (!match || Object.keys(match).length === 0) {
      return res.status(404).send("Match not found");
    }

    let mutualStats = [];
    let mutualMatches = [];
    let homeLastMatches = [];
    let awayLastMatches = [];

    if (match.mutual_id) {
      try {
        // 2. Load mutual stats
        const mutualResponse = await axios.get(
          `https://beta.kenyschulz.com/referee/api/getmutualstats.php?mutual_id=${match.mutual_id}`
        );
        mutualStats = mutualResponse.data || [];

        // 3. Load mutual matches
        const matchesResponse = await axios.get(
          `https://beta.kenyschulz.com/referee/api/getmatches.php?mutual_id=${match.mutual_id}`
        );
        mutualMatches = matchesResponse.data || [];
      } catch (err) {
        console.error("Error fetching mutual data:", err.message);
      }
    }

    // 4. Load last 5 matches for home and away teams
    try {
      if (match.hteam_id) {
        const homeResponse = await axios.get(
          `https://beta.kenyschulz.com/referee/api/getmatches.php?team_id=${match.hteam_id}&limit=5`
        );
        homeLastMatches = homeResponse.data || [];
      }

      if (match.ateam_id) {
        const awayResponse = await axios.get(
          `https://beta.kenyschulz.com/referee/api/getmatches.php?team_id=${match.ateam_id}&limit=5`
        );
        awayLastMatches = awayResponse.data || [];
      }
    } catch (err) {
      console.error("Error fetching last matches:", err.message);
    }

    // Render template with all data
    res.render("match-detail", {
      match,
      mutualStats,
      mutualMatches,
      homeLastMatches,
      awayLastMatches
    });
  } catch (err) {
    console.error("Error fetching match detail:", err.message);
    res.status(500).send("Error loading match detail");
  }
});
// Matchdetails Backend route end


module.exports = router;