const express = require("express");
const router = express.Router();
const pairController = require("../controllers/pairController");

// GET /pairs
router.get("/", pairController.getPairs);

// GET /pairs/:id - show a single referee detail
router.get("/:id", pairController.showPair);


module.exports = router;