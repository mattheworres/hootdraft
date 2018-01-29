angular.module('phpdraft.shared').directive('sectionError', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/templates/directives/shared/section_error.html',
    scope: {
      showError: '=',
      sectionName: '@sectionName',
    },
  })
);
