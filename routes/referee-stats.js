const express = require("express");
const router = express.Router();
const statsController = require("../controllers/statsController");

// GET /referees
router.get("/", statsController.getStats);

module.exports = router;