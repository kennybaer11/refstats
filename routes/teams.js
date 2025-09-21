const express = require("express");
const router = express.Router();
const teamsController = require("../controllers/teamsController");

// GET /teams
router.get("/", teamsController.getTeams);


// Team detail page
router.get("/:id", teamsController.showTeam);

module.exports = router;