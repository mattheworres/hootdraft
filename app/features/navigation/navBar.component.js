class NavController {
  constructor($rootScope, $scope, $routeParams, $location, messageService,
    authenticationService, subscriptionKeys, confirmActionService,
    $sessionStorage, api, errorService, draftService) {
    this.$rootScope = $rootScope;
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$location = $location;
    this.messageService = messageService;
    this.authenticationService = authenticationService;
    this.subscriptionKeys = subscriptionKeys;
    this.confirmActionService = confirmActionService;
    this.$sessionStorage = $sessionStorage;
    this.api = api;
    this.errorService = errorService;
    this.draftService = draftService;
  }

  $onInit() {
    this.draftNavHidden = true;
    this.status = this.draftService.getStatus();
    this.draft = {};

    this.deregisterDraftUpdated = this.$rootScope.$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
      const {status, draft} = args;

      this.status = status;
      this.draft = draft;

      this.draftNavHidden = status.error || status.badConnection || !status.valid;
    });
  }

  $onDestroy() {
    this.deregisterDraftUpdated();
  }

  isAuthenticated() {
    return this.authenticationService.isAuthenticated();
  }

  isAdmin() {
    return this.isAuthenticated() && this.authenticationService.isAdmin();
  }

  authenticatedName() {
    return this.$sessionStorage.user_name;
  }

  logOut() {
    this.authenticationService.logout();
    this.messageService.showInfo('Logged Out');
    this.$location.path('/home');
  }
}

NavController.$inject = [
  '$rootScope',
  '$scope',
  '$routeParams',
  '$location',
  'messageService',
  'authenticationService',
  'subscriptionKeys',
  'confirmActionService',
  '$sessionStorage',
  'api',
  'errorService',
  'draftService',
];

angular.module('phpdraft.navigation').component('phpdNavBar', {
  controller: NavController,
  templateUrl: 'app/features/navigation/navBar.component.html',
});
