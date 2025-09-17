const express = require("express");
const router = express.Router();
const competitionController = require("../controllers/competitionController");

// GET /competitions
router.get("/", competitionController.getCompetitions);

// Single competition detail
router.get("/:id", competitionController.getCompetitionById);

module.exports = router;