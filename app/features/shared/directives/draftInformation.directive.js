angular.module('phpdraft.shared').directive('phpdDraftInformation', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/shared/directives/draftInformation.directive.html',
    scope: {
      draft: '=',
      status: '=',
    },
  })
);
