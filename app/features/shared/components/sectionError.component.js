angular.module('phpdraft.shared').component('phpdSectionError', {
  templateUrl: 'app/features/shared/components/sectionError.component.html',
  bindings: {
    showError: '<',
    sectionName: '@',
  },
});
