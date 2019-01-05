class LostPasswordController {
  constructor($q, ENV, $scope, messageService, workingModalService,
    authenticationService, vcRecaptchaService, errorService) {
    this.$q = $q;
    this.ENV = ENV;
    this.$scope = $scope;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.authenticationService = authenticationService;
    this.vcRecaptchaService = vcRecaptchaService;
    this.errorService = errorService;
  }

  $onInit() {
    this.$scope.showLostForm = true;

    if (this.authenticationService.isAuthenticated()) {
      this.authenticationService.sendAuthenticatedUserToPreviousPath();
      return;
    }

    this.$scope.recaptchaPublicKey = this.ENV.recaptchaPublicKey;

    this.$scope.setRecaptchaResponse = response => {
      this.form._recaptcha = response;
    };
  }

  submitClicked() {
    if (this.form.$valid) {
      this.lostPassword();
    }
  }

  lostFormIsInvalid() {
    return this.lostInProgress ||
      !this.form.$valid ||
      ((this.form._recaptcha === null ? null : this.form._recaptcha.length) === 0);
  }

  lostPassword() {
    this.workingModalService.openModal();

    const lostModel = {
      _email: this.form.email.$viewValue,
      _recaptcha: this.form._recaptcha,
    };

    this.lostInProgress = true;

    const lostResult = this.authenticationService.lostPassword(lostModel);

    this.messageService.closeToasts();

    const lostSuccessHandler = () => {
      this.lostInProgress = false;
      this.workingModalService.closeModal();

      this.$scope.userEmail = this.form.email.$viewValue;

      this.form.$setPristine();
      this.$scope.showLostForm = false;

      this.messageService.showInfo('Reset password email sent');
    };

    const lostFailureHandler = response => {
      this.lostInProgress = false;
      this.workingModalService.closeModal();

      const lostError = this.errorService.parseValidationErrorsFromResponse(response);

      return this.messageService.showError(`${lostError}`, 'Unable to Request New Password');
    };

    return lostResult.promise.then(lostSuccessHandler, lostFailureHandler);
  }
}

LostPasswordController.$inject = [
  '$q',
  'ENV',
  '$scope',
  'messageService',
  'workingModalService',
  'authenticationService',
  'vcRecaptchaService',
  'errorService',
];

angular.module('phpdraft.authentication').component('phpdLostPassword', {
  controller: LostPasswordController,
  templateUrl: 'app/features/authentication/lostPassword.component.html',
});
