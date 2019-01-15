class HomeController {
  constructor($scope,
    api, messageService) {
    this.$scope = $scope;
    this.api = api;
    this.messageService = messageService;
  }

  $onInit() {
    this.drafts = [];

    this.$scope.draftTableLoading = true;

    const draftSuccessHandler = data => {
      this.$scope.draftTableLoading = false;
      this.drafts = data;
    };

    const errorHandler = () => {
      this.$scope.draftTableLoading = false;
      this.messageService.showError('Unable to load drafts');
    };

    this.api.Draft.getDraftList({}, draftSuccessHandler, errorHandler);
  }
}

HomeController.$inject = [
  '$scope',
  'api',
  'messageService',
];

angular.module('phpdraft.home').component('phpdHome', {
  controller: HomeController,
  templateUrl: 'app/features/home/home.component.html',
});
