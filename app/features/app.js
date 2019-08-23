angular
  .module('phpdraft', [
    'ngAnimate',
    'ngSanitize',
    'phpdraft.admin',
    'phpdraft.authentication',
    'phpdraft.config',
    'phpdraft.draft',
    'phpdraft.env',
    'phpdraft.shared',
    'phpdraft.home',
    'phpdraft.navigation',
    'phpdraft.pick',
  ])
  .constant('lodash', window._); //eslint-disable-line angular/window-service
