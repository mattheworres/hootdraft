class LoginController {
  constructor($q, $scope, $routeParams, $rootScope, subscriptionKeys, api, messageService, workingModalService,
    authenticationService, pathHelperService, errorService, draftService) {
    this.$q = $q;
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$rootScope = $rootScope;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.authenticationService = authenticationService;
    this.pathHelperService = pathHelperService;
    this.errorService = errorService;
    this.draftService = draftService;

    this.setupOnetimeRouteChangeListener = this.setupOnetimeRouteChangeListener.bind(this);
  }

  $onInit() {
    this.showPassword = false;

    if (this.authenticationService.isAuthenticated()) {
      this.authenticationService.sendAuthenticatedUserToPreviousPath();
      return;
    }

    this.setupOnetimeRouteChangeListener();
  }

  passwordInputType() {
    return this.showPassword
      ? 'text'
      : 'password';
  }

  submitClicked() {
    if (this.form.$valid) {
      this.login();
    }
  }

  login() {
    this.workingModalService.openModal();

    const loginModel = {
      _email: this.form.email.$viewValue,
      _password: this.form.password.$viewValue,
    };

    const loginResult = this.authenticationService.login(loginModel);

    this.messageService.closeToasts();

    this.loginInProgress = true;

    const loginSuccessHandler = () => {
      this.loginInProgress = false;
      this.workingModalService.closeModal();

      this.pathHelperService.sendToPreviousPath();

      this.messageService.showSuccess(`Welcome back, ${this.authenticationService.currentUserName()}!`, 'Logged In');
    };

    const loginFailureHandler = response => {
      this.loginInProgress = false;
      this.workingModalService.closeModal();

      const loginError = this.errorService.parseValidationErrorsFromResponse(response);

      this.messageService.showError(`${loginError}`, 'Unable to Login');
    };

    loginResult.promise.then(loginSuccessHandler, loginFailureHandler);
  }

  //Once we login, wait and see if the next route has a draft_id, if so, ask the draftService to get it from the server again
  setupOnetimeRouteChangeListener() {
    const deregister = this.$rootScope.$on(this.subscriptionKeys.routeChangeSuccess, () => {
      const draftId = this.$routeParams.draft_id;
      const routeHasDraft = angular.isDefined(draftId) && draftId !== null;

      //In the weird instance we need to make sure a cached draft is reloaded BUT we weren't sent back to a draft-related page,
      //we need the draft service to remember this just this once and force another trip back to the server for us.
      this.draftService.setReloadFlag();

      if (routeHasDraft) this.$rootScope.$broadcast(this.subscriptionKeys.routeHasDraft, {hasDraft: true, needsReloaded: true});
      deregister();
    });
  }
}

LoginController.$inject = [
  '$q',
  '$scope',
  '$routeParams',
  '$rootScope',
  'subscriptionKeys',
  'api',
  'messageService',
  'workingModalService',
  'authenticationService',
  'pathHelperService',
  'errorService',
  'draftService',
];

angular.module('phpdraft.authentication').component('phpdLogin', {
  controller: LoginController,
  templateUrl: 'app/features/authentication/login.component.html',
});
