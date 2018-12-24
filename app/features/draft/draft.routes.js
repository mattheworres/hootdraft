angular.module('phpdraft.draft').config(($routeProvider, $locationProvider) => {
  $locationProvider
    .html5Mode(true)
    .hashPrefix('!');

  $routeProvider.when('/draft/:draft_id', {
    template: '<phpd-draft-index></phpd-draft-index>',
  });

  $routeProvider.when('/draft/:draft_id/board', {
    template: '<phpd-board></phpd-board>',
  });

  $routeProvider.when('/commish/draft/create', {
    template: '<phpd-draft-create></phpd-draft-create>',
  });

  $routeProvider.when('/commish/draft/:draft_id/edit', {
    template: '<phpd-draft-edit></phpd-draft-edit>',
  });

  $routeProvider.when('/commish/draft/:draft_id/timers', {
    template: '<phpd-pick-timers></phpd-pick-timers>',
  });

  $routeProvider.when('/draft/:draft_id/depth_chart', {
    template: '<phpd-depth-charts></phpd-depth-charts>',
  });

  $routeProvider.when('/draft/:draft_id/trades', {
    template: '<phpd-trades></phpd-trades>',
  });

  $routeProvider.when('/commish/draft/:draft_id/trades/add', {
    template: '<phpd-add-trade></phpd-add-trade>',
  });

  $routeProvider.when('/draft/:draft_id/stats', {
    template: '<phpd-stats></phpd-stats>',
  });
});
