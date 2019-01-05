class DraftPasswordModalController {
  constructor($rootScope, $sessionStorage, $routeParams,
    authenticationService, subscriptionKeys, $location) {
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

    this.close();
  }

  gotoLogin() {
    this.$location.path('login');
    this.dismiss();
  }
}

DraftPasswordModalController.$inject = [
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
    dismiss: '&',
    close: '&',
  },
});
