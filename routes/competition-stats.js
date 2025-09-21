const express = require('express');
const router = express.Router();

// Make sure the path and export are correct
const competitionStatsController = require('../controllers/competitionStatsController');

// Page route
router.get('/', competitionStatsController.renderPage);  // ✅ must be a function

// AJAX data route
router.get('/data', competitionStatsController.getData);  // ✅ must be a function

module.exports = router;