const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD_HASH = '$2b$10$l/OMF3ME4Ru48yWFLvabN.7vCMFgejCxe.Q30U6iE2YXI9KuES5Y.';

exports.getDashboard = (req, res) => {
  res.render('admin-dashboard'); // simple page with links
};

exports.getLoginPage = (req, res) => {
  res.render('admin-login', { error: null });
};

exports.postLogin = async (req, res) => {
  const { username, password } = req.body;
  if (username === ADMIN_USERNAME) {
    const bcrypt = require('bcrypt');
    const match = await bcrypt.compare(password, ADMIN_PASSWORD_HASH);
    if (match) {
      req.session.isLoggedIn = true;
      return res.redirect('/admin');
    }
  }
  res.render('admin-login', { error: 'Invalid credentials' });
};

exports.logout = (req, res) => {
  req.session.destroy(() => res.redirect('/admin/login'));
};