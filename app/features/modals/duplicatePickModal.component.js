class DuplicatePickModalController {
  constructor(draftService) {
    this.draftService = draftService;
  }

  $onInit() {
    this.draftService.getDraft().then(draft => {
      this.draft = draft;
    });
  }

  yesClicked() {
    this.close();
  }

  noClicked() {
    this.dismiss();
  }

  cancel() {
    this.dismiss();
  }
}

DuplicatePickModalController.$inject = [
  'draftService',
];

angular.module('phpdraft.modals').component('phpdDuplicatePickModal', {
  controller: DuplicatePickModalController,
  templateUrl: 'app/features/modals/duplicatePickModal.component.html',
  bindings: {
    currentPick: '<',
    duplicateMatches: '<',
    close: '&',
    dismiss: '&',
  },
});
