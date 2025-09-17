const express = require("express");
const router = express.Router();
const seasonController = require("../controllers/seasonController");

// GET /seasons
router.get("/", seasonController.getSeasons);

module.exports = router;