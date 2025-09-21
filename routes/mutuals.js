const express = require("express");
const router = express.Router();
const mutualController = require("../controllers/mutualController");

// GET /mutuals
router.get("/", mutualController.getMutuals);

// one mutual (id from DB, e.g. "spartavsdukl")
router.get("/:mutualId", mutualController.getMutualStats);

module.exports = router;