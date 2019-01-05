angular.module('phpdraft.pick').directive('phpdUpcomingPicks', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/pick/lists/upcomingPicks.directive.html',
    scope: {
      draft: '=',
      nextPicks: '=',
      nextLoading: '=',
      nextError: '=',
    },
  })
);
