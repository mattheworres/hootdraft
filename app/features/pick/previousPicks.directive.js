angular.module('phpdraft.pick').directive('phpdPreviousPicks', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/pick/previousPicks.directive.html',
    scope: {
      draft: '=',
      previousPicks: '=',
      previousLoading: '=',
      previousError: '=',
    },
  })
);
