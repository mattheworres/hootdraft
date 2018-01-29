class LostPasswordController {
  constructor($q, ENV, $scope, messageService, workingModalService,
    authenticationService, vcRecaptchaService) {
    this.$q = $q;
    this.ENV = ENV;
    this.$scope = $scope;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.authenticationService = authenticationService;
    this.vcRecaptchaService = vcRecaptchaService;
  }

  $onInit() {
    this.$scope.showLostForm = true;

    if (this.authenticationService.isAuthenticated()) {
      this.authenticationService.sendAuthenticatedUserToPreviousPath();
      return;
    }

    this.$scope.recaptchaPublicKey = this.ENV.recaptchaPublicKey;

    this.$scope.setRecaptchaResponse = response => {
      this.form._recaptcha = response; // eslint-disable-line no-underscore-dangle
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
      ((this.form._recaptcha === null ? null : this.form._recaptcha.length) === 0); // eslint-disable-line no-underscore-dangle
  }

  lostPassword() {
    this.workingModalService.openModal();

    const lostModel = {
      _email: this.form.email.$viewValue,
      _recaptcha: this.form._recaptcha, // eslint-disable-line no-underscore-dangle
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
      let lostError;
      this.lostInProgress = false;
      this.workingModalService.closeModal();

      if (angular.isDefined(response) && angular.isDefined(response.data) && response.data.status === 400) {
        lostError = angular.isDefined(response.data.data) &&
          angular.isDefined(response.data.data.errors)
          ? response.data.data.errors.join('\n')
          : 'Unknown 400 error';
      } else {
        lostError = `Whoops! We hit a snag - looks like it's on our end (${response.data.status})`;
      }

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
];

angular.module('phpdraft.authentication').component('lostPassword', {
  controller: LostPasswordController,
  templateUrl: 'app/features/authentication/lostPassword.component.html',
});
