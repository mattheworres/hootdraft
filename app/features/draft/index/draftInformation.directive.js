angular.module('phpdraft.draft').directive('phpdDraftInformation', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/draft/index/draftInformation.directive.html',
    scope: {
      draft: '=',
      status: '=',
    },
  })
);
