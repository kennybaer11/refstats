const express = require('express');
const router = express.Router();
const homesearchController = require('../controllers/homesearchController');

// GET /search?q=...
router.get('/', homesearchController.search);

module.exports = router;