const express = require("express");
const router = express.Router();
const pairstatsController = require("../controllers/pairstatsController");

// GET /referees
router.get("/", pairstatsController.getPairstats);

module.exports = router;