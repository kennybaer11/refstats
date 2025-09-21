const express = require("express");
const router = express.Router();
const phasedetailController = require("../controllers/phasedetailController");

// GET /phases
router.get("/", phasedetailController.getPhasedetail);

module.exports = router;