class CommishActionsController {
  constructor(messageService, draftService, draftModalService) {
    this.messageService = messageService;
    this.draftService = draftService;
    this.draftModalService = draftModalService;
  }

  $onInit() {
    this.draftService.getDraft().then(draft => {
      this.draft = draft;
      this.status = this.draftService.getStatus();
    }, () => {
      this.messageService.showError('Unable to load draft');
    });
  }

  showStartDraftModal() {
    this.draftModalService.showStartDraftModal(this.draft.draft_id);
  }

  showResetDraftModal() {
    this.draftModalService.showResetDraftModal(this.draft.draft_id);
  }

  showDeleteDraftModal() {
    this.draftModalService.showDeleteDraftModal(this.draft.draft_id);
  }
}

CommishActionsController.$inject = [
  'messageService',
  'draftService',
  'draftModalService',
];

angular.module('phpdraft.draft').component('phpdCommishActions', {
  controller: CommishActionsController,
  templateUrl: 'app/features/draft/index/commishActions.component.html',
});
