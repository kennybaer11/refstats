const bcrypt = require('bcrypt');

async function generateHash() {
  const hash = await bcrypt.hash('Dukla123.', 10);
  console.log(hash);
}

generateHash().catch(console.error);