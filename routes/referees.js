const express = require("express");
const router = express.Router();
const refereeController = require("../controllers/refereeController");

// GET /referees
router.get("/", refereeController.getReferees);

// GET /referees/:id - show a single referee detail
router.get("/:id", refereeController.showReferee);

module.exports = router;