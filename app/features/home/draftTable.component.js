class DraftTableController {
  constructor(api, messageService, lodash, authenticationService) {
    this.api = api;
    this.messageService = messageService;
    this.lodash = lodash;
    this.authenticationService = authenticationService;
  }

  $onInit() {
    this.displayedDrafts = [];
    this.sports = [];
    this.statuses = [];
    this.filteredSport = null;
    this.filteredStatus = null;

    this.getters = {
      createdDate: row => new Date(row.draft_created_date),
      status: row => {
        if (row.setting_up) return 0;
        else if (row.in_progress) return 1;
        else if (row.completed) return 2;
        return 3;
      },
    };

    this.api.Resources.draftOptions({}, response => {
      const removeEntry = 'remove';
      this.sports = response.sports;
      this.statuses = response.statuses;
      this.sports.remove = removeEntry;
      this.statuses.remove = removeEntry;
    }, () => {
      this.messageService.showError('Cannot load draft options', 'Error');
    });

    this.isAuthenticated = this.authenticationService.isAuthenticated();
  }

  $onChanges(chchchchanges) {
    const {drafts} = chchchchanges;
    if (drafts && drafts.currentValue.length > 0) {
      this.originalDrafts = drafts;
      this.showPaging = drafts.currentValue.length > this.itemsByPage;
    }
  }
}

DraftTableController.$inject = [
  'api',
  'messageService',
  'lodash',
  'authenticationService',
];

angular.module('phpdraft.home').component('phpdDraftTable', {
  controller: DraftTableController,
  templateUrl: 'app/features/home/draftTable.component.html',
  bindings: {
    drafts: '<',
    itemsByPage: '<',
  },
});

