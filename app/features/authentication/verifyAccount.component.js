class VerifyAccountController {
  constructor($q, $routeParams, messageService,
    workingModalService, authenticationService, $location,
    $timeout, errorService) {
    this.$q = $q;
    this.$routeParams = $routeParams;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.authenticationService = authenticationService;
    this.$location = $location;
    this.$timeout = $timeout;
    this.errorService = errorService;
  }

  $onInit() {
    this.showErrorInformation = false;

    if (this.authenticationService.isAuthenticated()) {
      this.authenticationService.sendAuthenticatedUserToPreviousPath();
      return;
    }

    const {email, token} = this.$routeParams;

    if ((email === null) || (token === null)) {
      this.messageService.showError('Invalid link or email');
      this.showErrorInformation = true;
      return;
    }

    this.workingModalService.openModal(3100, 100);

    const successHandler = data => {
      this.$timeout(() => {
        this.workingModalService.closeModal();

        this.showErrorInformation = false;

        this.authenticationService.cacheSession(data.data);

        this.$location.path('/home');

        this.messageService.showInfo('Your email is now verified, and you are now logged in. Welcome!');
      }, 3500);
    };

    const failureHandler = response => {
      this.workingModalService.closeModal();

      this.showErrorInformation = true;

      const emailVerificationError = this.errorService.parseValidationErrorsFromResponse(response);

      this.messageService.showError(`${emailVerificationError}`, 'Unable to verify your email');
    };

    const emailVerificationModel = {
      _email: email,
      _emailVerificationKey: token,
    };

    const emailVerificationResult = this.authenticationService.emailVerification(emailVerificationModel);

    this.messageService.closeToasts();

    emailVerificationResult.promise.then(successHandler, failureHandler);
  }
}

VerifyAccountController.$inject = [
  '$q',
  '$routeParams',
  'messageService',
  'workingModalService',
  'authenticationService',
  '$location',
  '$timeout',
  'errorService',
];

angular.module('phpdraft.authentication').component('phpdVerifyAccount', {
  controller: VerifyAccountController,
  templateUrl: 'app/features/authentication/verifyAccount.component.html',
});
