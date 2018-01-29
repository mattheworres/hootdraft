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
    this.$scope.showPassword = false;

    if (this.authenticationService.isAuthenticated()) {
      this.messageService.showInfo(
        `Already logged in as ${this.authenticationService.currentUserName()}.`,
        'Logged In');
      return this.pathHelperService.sendToPreviousPath();
    }

    return null;
  }

  passwordInputType() {
    if (this.$scope.showPassword) {
      return 'text';
    }

    return 'password';
  }

  submitClicked() {
    if (this.form.$valid) {
      return this.login();
    }

    return null;
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

      return this.messageService.showSuccess(`Welcome back, ${this.authenticationService.currentUserName()}!`, 'Logged In');
    };

    const loginFailureHandler = response => {
      let loginError;
      this.loginInProgress = false;
      this.workingModalService.closeModal();

      if (response.status === 400) {
        loginError = guard(response.data.data === null ? null : response.data.data.errors, x => x.join('\n')); // eslint-disable-line no-use-before-define
      } else {
        loginError = `Whoops! We hit a snag - looks like it's on our end (${response.data.status})`;
      }

      return this.messageService.showError(`${loginError}`, 'Unable to Login');
    };

    return loginResult.promise.then(loginSuccessHandler, loginFailureHandler);
  }
}

function guard(value, transform) {
  return (angular.isDefined(value) && value !== null) ? transform(value) : null;
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
