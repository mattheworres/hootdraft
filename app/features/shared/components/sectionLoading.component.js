angular.module('phpdraft.shared').component('phpdSectionLoading', {
  templateUrl: 'app/features/shared/components/sectionLoading.component.html',
  bindings: {
    showLoading: '<',
    loadingText: '@',
  },
});
