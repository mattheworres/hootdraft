class DraftModalService {
  constructor($sessionStorage, $uibModal, confirmActionService, messageService,
    draftService) {
    this.$sessionStorage = $sessionStorage;
    this.$uibModal = $uibModal;
    this.confirmActionService = confirmActionService;
    this.messageService = messageService;
    this.draftService = draftService;
  }

  showPasswordModal(draftName) {
    const cachedPassword = this.$sessionStorage.draft_password === null
      ? ''
      : this.$sessionStorage.draft_password;

    this.modalInstance = this.$uibModal.open({
      template: `<phpd-draft-password-modal draftName="${draftName}"
        draftPassword="${cachedPassword}" /></phpd-draft-password-modal>`,
    });
  }

  showAddManagersModal(draftId) {
    this.modalInstance = this.$uibModal.open({
      template: `<phpd-add-managers-modal draftId="${draftId}></phpd-add-managers-modal>`,
    });
  }

  closeModal() {
    if (angular.isDefined(this.modalInstance)) {
      this.modalInstance.close();
    }
  }

  showStartDraftModal(draftId) {
    const title = 'Start draft?';
    const message = 'Cool, ready to start your draft? Just make sure all managers have been added and your league\'s details are correct - you can\'t change them once your draft has been started. Are you ready to get this show on the road?';
    const iconClass = 'fa-play';
    const confirmButtonText = 'Yep! Let\'s do this!';

    const startDraft = () => {
      this.draftService.startDraft(draftId);
    };

    return this.confirmActionService.showConfirmationModal(message, startDraft, title, iconClass, confirmButtonText);
  }

  showResetDraftModal(draftId) {
    const title = 'Reset draft?';
    const message = 'Uh oh, something wrong? No problem, we can reset your draft. Fair warning though - any and all picks or trades you\'ve made will be deleted forever. Are you sure?';
    const iconClass = 'fa-exclamation-triangle';
    const confirmButtonText = 'Yes, reset my draft';

    const resetDraft = () => {
      this.draftService.resetDraft(draftId);
    };

    return this.confirmActionService.showConfirmationModal(message, resetDraft, title, iconClass, confirmButtonText);
  }

  showDeleteDraftModal(draftId) {
    const title = 'Delete the draft?';
    const message = 'Are you sure you want to delete the draft? This action cannot be undone.';
    const iconClass = 'fa-trash';
    const confirmButtonText = 'Yes, Delete the draft';

    const deleteDraft = () => {
      this.draftService.deleteDraft(draftId);
    };

    return this.confirmActionService.showConfirmationModal(message, deleteDraft, title, iconClass, confirmButtonText);
  }
}

DraftModalService.$inject = [
  '$sessionStorage',
  '$uibModal',
  'confirmActionService',
  'messageService',
  'draftService',
];

angular.module('phpdraft.shared').service('draftModalService', DraftModalService);
