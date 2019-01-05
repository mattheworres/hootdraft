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

angular.module('phpdraft.shared').component('phpdDraftState', {
  controller: DraftStateController,
  templateUrl: 'app/features/shared/components/draftState.component.html',
  bindings: {
    commishName: '<',
    draftStatus: '<',
  },
});
