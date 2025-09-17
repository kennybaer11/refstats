const bcrypt = require('bcrypt');

const ADMIN_PASSWORD_HASH = 'paste_your_hash_here';
const passwordToTest = 'YourPasswordHere'; // <-- your password

bcrypt.compare(passwordToTest, ADMIN_PASSWORD_HASH).then(match => {
  if (match) {
    console.log('Password matches hash!');
  } else {
    console.log('Password does NOT match hash.');
  }
}).catch(console.error);