angular.module('phpdraft.draft').directive('phpdDraftCompleted', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/draft/index/draftCompleted.directive.html',
    scope: {
      draft: '=',
      status: '=',
      roundPicks: '=',
      selectedDraftRound: '=',
      pagerItemTally: '=',
      roundError: '=',
      roundLoading: '=',
    },
  })
);
