const del = require('del');

function clean(done) {
  return del([
    'css/**/*',
    'fonts/**/*',
    'img/**/*',
    'js/**/*',
    'index.html',
  ],
  {force: true},
  done);
}

module.exports = clean;
