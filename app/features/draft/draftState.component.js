class DraftStateController {
  constructor(draftModalService) {
    this.draftModalService = draftModalService;
  }

  showDraftPasswordModal() {
    return this.draftModalService.showPasswordModal();
  }
}

DraftStateController.$inject = [
  'draftModalService',
];

angular.module('phpdraft.draft').component('phpdDraftState', {
  controller: DraftStateController,
  templateUrl: 'app/features/draft/draftState.component.html',
  bindings: {
    commishName: '<',
    draftStatus: '<',
  },
});
