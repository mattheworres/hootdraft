angular.module('phpdraft.shared').component('phpdDraftLabel', {
  templateUrl: 'app/features/shared/components/draftLabel.component.html',
  bindings: {
    labelType: '@',
    labelIdentifier: '@',
    labelDisplay: '@',
  },
});
