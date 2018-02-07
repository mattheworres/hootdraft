class LoginController {
  constructor($q, $scope, api, messageService, workingModalService,
    authenticationService, pathHelperService, errorService) {
    this.$q = $q;
    this.$scope = $scope;
    this.api = api;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.authenticationService = authenticationService;
    this.pathHelperService = pathHelperService;
    this.errorService = errorService;
  }

  $onInit() {
    this.showPassword = false;

    if (this.authenticationService.isAuthenticated()) {
      this.authenticationService.sendAuthenticatedUserToPreviousPath();
      return;
    }
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
}

LoginController.$inject = [
  '$q',
  '$scope',
  'api',
  'messageService',
  'workingModalService',
  'authenticationService',
  'pathHelperService',
  'errorService',
];

angular.module('phpdraft.authentication').component('phpdLogin', {
  controller: LoginController,
  templateUrl: 'app/features/authentication/login.component.html',
});
