angular.module('phpdraft.pick').directive('phpdManagerPicksDisplay', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/pick/managerPicksDisplay.directive.html',
    scope: {
      draft: '=',
      managers: '=',
      selectedManager: '=',
      managerPicks: '=',
    },
  })
);
