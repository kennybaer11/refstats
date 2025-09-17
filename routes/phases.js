const express = require("express");
const router = express.Router();
const phaseController = require("../controllers/phaseController");

// GET /phases
router.get("/", phaseController.getPhases);

module.exports = router;