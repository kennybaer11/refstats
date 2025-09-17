const express = require('express');
const bcrypt = require('bcrypt');
const { ensureLoggedIn } = require('../middleware/auth');
const adminController = require('../controllers/adminController');
const newsController = require('../controllers/newsController');
const commentsController = require('../controllers/commentsController');

const router = express.Router();

// Dashboard
router.get('/', ensureLoggedIn, adminController.getDashboard);

// Login
router.get('/login', adminController.getLoginPage);
router.post('/login', adminController.postLogin);

// Logout
router.get('/logout', adminController.logout);

// Add news
router.get('/add-news', ensureLoggedIn, newsController.getAddNewsPage);

// Comments moderation
router.get('/comments', ensureLoggedIn, commentsController.getCommentsPage);
// Bulk comments
router.post('/bulk-comments', ensureLoggedIn, commentsController.bulkComments);
router.post('/approve-comment/:id', ensureLoggedIn, commentsController.approveComment);
router.delete('/delete-comment/:id', ensureLoggedIn, commentsController.deleteComment);

module.exports = router;
