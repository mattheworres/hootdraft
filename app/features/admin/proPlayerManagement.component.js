class ProPlayerManagementController {
  constructor($timeout,
    api,
    ENV,
    messageService,
    workingModalService,
    Upload) {
    this.$timeout = $timeout;
    this.api = api;
    this.ENV = ENV;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.Upload = Upload;
  }

  $onInit() {
    this.leagues = [];
    this.progress = 100;

    const leaguesSuccess = response => {
      this.leagues = response;
    };

    const leaguesError = () => this.messageService.showError('Unable to load league data, check dev tools and report findings to the open source project (please!)');

    this.api.Admin.getLeagues({}, leaguesSuccess, leaguesError);
    this.csvFile = {};
    this.uploadInProgress = false;
  }

  changeLeague(league, leagueName) {
    this.league = league;
    this.leagueName = leagueName;
  }

  submitClicked() {
    if (!this.form.$valid || angular.isUndefined(this.league)) {
      this.messageService.showWarning('You must select a league AND a CSV file first!');
      return;
    }

    this.uploadInProgress = true;
    this.progress = 10;

    this.form.csvFile.upload = this.Upload.upload({
      url: `${this.ENV.apiEndpoint}admin/proplayers`,
      method: 'POST',
      objectKey: '[csvFile]',
      data: {file: this.form.csvFile.$modelValue, league: this.league},
    });

    this.form.csvFile.upload.then((response => this.$timeout(() => {
      this.form.csvFile.result = response.data;
      this.uploadInProgress = false;
      this.form.csvFile.upload = {};
      this.messageService.showSuccess(`${this.league} player data updated!`);
      this.progress = 100;
      delete this.league;
    })
    ), (response => {
        if (response.status > 0) {
          const errors = response.data.errors.join('; ');
          this.messageService.showError(`Unable to upload player data: ${errors}`);
          this.uploadInProgress = false;
          this.progress = 0;
        }
      }
      ), evt => {
        this.progress = Math.min(100, parseInt((100 * evt.loaded) / evt.total, 10));
      });
  }
}
ProPlayerManagementController.$inject = [
  '$timeout',
  'api',
  'ENV',
  'messageService',
  'workingModalService',
  'Upload',
];

angular.module('phpdraft.admin').component('phpdProPlayerManagement', {
  controller: ProPlayerManagementController,
  templateUrl: 'app/features/admin/proPlayerManagement.component.html',
});
