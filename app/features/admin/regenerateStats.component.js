class RegenerateStatsController {
  constructor($timeout,
    api,
    ENV,
    messageService,
    workingModalService,
    DTOptionsBuilder,
    DTColumnDefBuilder) {
    this.$timeout = $timeout;
    this.api = api;
    this.ENV = ENV;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.DTOptionsBuilder = DTOptionsBuilder;
    this.DTColumnDefBuilder = DTColumnDefBuilder;
  }

  $onInit() {
    this.drafts = [];

    this._loadDrafts();
  }

  _loadDrafts() {
    this.workingModalService.openModal();

    const draftsSuccess = response => {
      this.workingModalService.closeModal();
      this.drafts = response;
    };

    const draftsError = () => {
      this.workingModalService.closeModal();
      this.messageService.showError('Unable to load drafts, check dev tools and report findings to the open source project (please!)');
    };

    this.api.Admin.getAllDrafts({}, draftsSuccess, draftsError);
  }

  regenerateStats(draft_id) {
    const draftId = parseInt(draft_id, 10);

    if (draftId === 0) {
      this.messageService.showWarning('Can\'t regenerate stats, invalid draft id.');
      return;
    }

    this.workingModalService.openModal();
    this.regenerateInProgress = true;

    const regenerateSuccess = () => {
      this.workingModalService.closeModal();
      this.regenerateInProgress = false;
      this._loadDrafts();
      this.messageService.showSuccess('Stats regenerated!');
    };

    const regenerateError = response => {
      this.workingModalService.closeModal();
      this.regenerateInProgress = false;
      const errors = response.data.error;
      this.messageService.showError(`Unable to regenerate stats: ${errors}`);
    };

    this.api.Admin.regenerateDraftStats({draft_id: draftId}, regenerateSuccess, regenerateError);
  }
}

RegenerateStatsController.$inject = [
  '$timeout',
  'api',
  'ENV',
  'messageService',
  'workingModalService',
  'DTOptionsBuilder',
  'DTColumnDefBuilder',
];

angular.module('phpdraft.admin').component('phpdRegenerateStats', {
  controller: RegenerateStatsController,
  templateUrl: 'app/features/admin/regenerateStats.component.html',
});
