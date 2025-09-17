function ensureLoggedIn(req, res, next) {
  if (req.session && req.session.isLoggedIn) {
    next();
  } else {
    res.redirect('/admin/login');
  }
}

module.exports = { ensureLoggedIn };