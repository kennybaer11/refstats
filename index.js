const express = require('express');
const session = require('express-session');
const adminRoutes = require('./routes/admin');
const axios = require('axios');
const app = express();
const PORT = 3000;
const path = require('path');

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(session({
  secret: process.env.SESSION_SECRET || 'supersecretkey', // use env var in prod
  resave: false,
  saveUninitialized: false,
  cookie: { secure: false } // set true if HTTPS in prod
}));

app.use(express.static('public'));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use('/admin', adminRoutes);

app.get('/', async (req, res) => {
  try {
    const competition_id = req.query.competition_id || '';

    // Fetch news
    const newsResponse = await axios.get('https://beta.kenyschulz.com/referee/api/getnews.php');
    const news = newsResponse.data;
    news.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    // Fetch upcoming fixtures
    const fixturesUrl = `https://beta.kenyschulz.com/referee/api/getfixtures.php?limit=10${competition_id ? `&competition_id=${competition_id}` : ''}`;
    const fixturesResponse = await axios.get(fixturesUrl);
    const fixtures = fixturesResponse.data.success ? fixturesResponse.data.fixtures : [];

    // Fetch competitions
    const competitionsResponse = await axios.get('https://beta.kenyschulz.com/referee/api/getcompetitions.php');
    const competitions = competitionsResponse.data || [];

    res.render('index', { 
      news, 
      fixtures, 
      competitions, 
      activeCompetition: competition_id
    });
  } catch (err) {
    console.error('Error loading homepage:', err);
    res.status(500).send('Error loading homepage');
  }
});


// news route start
const newsRoutes = require("./routes/news");
app.use("/news", newsRoutes);

// search route
const searchRoutes = require('./routes/search');
app.use('/search', searchRoutes);

// competitions route start
const competitionsRoutes = require("./routes/competitions");
app.use("/competitions", competitionsRoutes);

// competition stats route start
const competitionstatsRoutes = require("./routes/competition-stats");
app.use("/competition-stats", competitionstatsRoutes);

// seasons route start
const seasonsRoutes = require("./routes/seasons");
app.use("/seasons", seasonsRoutes);

// Referees route start
const refereeRoutes = require("./routes/referees");
app.use("/referees", refereeRoutes);

// Individual referee page (must come **after** the list route)
const refereeController = require('./controllers/refereeController'); // or your actual controller
app.get('/referees/:id', refereeController.showReferee);

// Referees stats route start
const statsRoutes = require("./routes/referee-stats");
app.use("/referee-stats", statsRoutes);

// Referees pair stats route start
const pairstatsRoutes = require("./routes/referee-pair-stats");
app.use("/referee-pair-stats", pairstatsRoutes);

// Pair route start
const pairRoutes = require("./routes/pairs");
app.use("/pairs", pairRoutes);

//  referee pair page (must come **after** the list route)
const pairController = require('./controllers/pairController'); // or your actual controller
app.get('/pairs/:id', pairController.showPair);

// Teams route start
const teamsRoutes = require("./routes/teams");
app.use("/teams", teamsRoutes);


// Mutuals route start
const mutualRoutes = require("./routes/mutuals");
app.use("/mutuals", mutualRoutes);


// Matches route start
const matchesRoutes = require("./routes/matches");
app.use("/matches", matchesRoutes);


// Pair route start
const phaseRoutes = require("./routes/phases");
app.use("/phases", phaseRoutes);


// Pair route start
const phasedetailRoutes = require("./routes/phasedetail");
app.use("/phasedetail", phasedetailRoutes);



// Start server
app.listen(PORT, () => {
  console.log(`Express server running at http://localhost:${PORT}`);
});
