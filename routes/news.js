const express = require("express");
const router = express.Router();
const newsController = require("../controllers/newsController");

// GET /news
router.get("/", newsController.getNews);
router.get("/:id", newsController.getNewsDetail); // /news/:id
router.get("/admin/add", newsController.getAddNewsPage);

module.exports = router;