const fetch = require("node-fetch");

exports.getPairstats = async (req, res) => {
  try {
    // pass query params from request directly to API
    const query = new URLSearchParams(req.query).toString();
    const apiUrl = `https://beta.kenyschulz.com/referee/api/getpairstats.php?${query}`;

    const response = await fetch(apiUrl);
    const data = await response.json();

    // Extract stats + filters
    const pairstats = data.stats || data;   // stats array
    const filters = data.filters || {};     // available filter options

    const rawQuery = req.query;

    // Map API param names to form field names
    const activeFilters = {
      competition: rawQuery.competition_id || "",
      season: rawQuery.season_id || "",
      phase: rawQuery.phase_id || "",
      phasedetail: rawQuery.phasedetail_id || "",
      groupby: rawQuery.group || ""
    };

    res.render("referee-pair-stats", { pairstats, filters, activeFilters });
  } catch (err) {
    console.error("Error fetching pair stats:", err);
    res.status(500).send("Error loading referee pair stats");
  }
};
