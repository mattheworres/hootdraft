angular.module('phpdraft.pick').directive('phpdPicksByRound', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/pick/picksByRound.directive.html',
    scope: {
      draft: '=',
      selectedDraftRound: '=',
      picks: '=',
      pagerItemTally: '=',
      picksLoading: '=',
      selectedDraftRoundUpdate: '&',
    },
  })
);
