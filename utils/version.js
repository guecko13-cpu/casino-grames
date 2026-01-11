const { version } = require('../package.json');

function buildVersion() {
  return version || '0.0.0';
}

module.exports = { buildVersion };
