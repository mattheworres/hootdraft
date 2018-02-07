class DraftPasswordModalController {
  constructor($uibModalInstance, $rootScope, $sessionStorage, $routeParams,
    authenticationService, subscriptionKeys, $location) {
    this.$uibModalInstance = $uibModalInstance;
    this.$rootScope = $rootScope;
    this.$sessionStorage = $sessionStorage;
    this.$routeParams = $routeParams;
    this.authenticationService = authenticationService;
    this.subscriptionKeys = subscriptionKeys;
    this.$location = $location;
  }

  $onInit() {
    this.password = this.$sessionStorage.draft_password;
  }

  isUnauthenticated() {
    return !this.authenticationService.isAuthenticated();
  }

  setPassword() {
    this.$sessionStorage.draft_password = this.form.password.$viewValue; // eslint-disable-line camelcase

    this.$rootScope.$broadcast(this.subscriptionKeys.reloadDraft, {draft_id: this.$routeParams.draft_id, hasResetPassword: true}); // eslint-disable-line camelcase
    this.cancel();
  }

  gotoLogin() {
    this.$location.path('login');
    this.cancel();
  }

  cancel() {
    this.$uibModalInstance.dismiss('closed');
  }
}

DraftPasswordModalController.$inject = [
  '$uibModalInstance',
  '$rootScope',
  '$sessionStorage',
  '$routeParams',
  'authenticationService',
  'subscriptionKeys',
  '$location',
];

angular.module('phpdraft.modals').component('phpdDraftPasswordModal', {
  controller: DraftPasswordModalController,
  templateUrl: 'app/features/modals/draftPasswordModal.component.html',
  bindings: {
    draftName: '<',
    draftPassword: '<',
  },
});
