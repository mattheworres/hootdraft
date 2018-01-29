class LoginController {
  constructor($q, $scope, api, messageService, workingModalService,
    authenticationService, pathHelperService) {
    this.$q = $q;
    this.$scope = $scope;
    this.api = api;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.authenticationService = authenticationService;
    this.pathHelperService = pathHelperService;
    this.passwordInputType = this.passwordInputType.bind(this);
    this.submitClicked = this.submitClicked.bind(this);
    this.login = this.login.bind(this);
  }

  $onInit() {
    this.showPassword = false;

    if (this.authenticationService.isAuthenticated()) {
      this.messageService.showInfo(
        `Already logged in as ${this.authenticationService.currentUserName()}.`,
        'Logged In');
      this.pathHelperService.sendToPreviousPath();
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
      let loginError;
      this.loginInProgress = false;
      this.workingModalService.closeModal();

      if (response.status === 400) {
        loginError = angular.isDefined(response.data.data) &&
          angular.isDefined(response.data.data.errors)
          ? response.data.data.errors.join('\n')
          : 'Unknown 400 error';
      } else {
        loginError = `Whoops! We hit a snag - looks like it's on our end (${response.data.status})`;
      }

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
];

angular.module('phpdraft.authentication').component('login', {
  controller: LoginController,
  templateUrl: 'app/features/authentication/login.component.html',
});
