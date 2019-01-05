angular.module('phpdraft.draft').directive('phpdDraftInProgress', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/draft/index/draftInProgress.directive.html',
    scope: {
      draft: '=',
      status: '=',
      lastFivePicks: '=',
      nextFivePicks: '=',
      lastFiveLoading: '=',
      nextFiveLoading: '=',
      selectedDraftRoundUpdate: '&',
    },
  })
);
