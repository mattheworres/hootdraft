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
      this.messageService.showError('Invalid verification data');
      this.showErrorInformation = true;
      return;
    }

    this.workingModalService.openModal(3100, 100);

    const successHandler = () => {
      this.$timeout(() => {
        this.workingModalService.closeModal();

        this.showErrorInformation = false;

        this.$location.path('/login');

        this.messageService.showInfo('Your account has been enabled - you may log in now');
      }, 3500);
    };

    const failureHandler = response => {
      this.workingModalService.closeModal();

      this.showErrorInformation = true;

      const verifyError = this.errorService.parseValidationErrorsFromResponse(response);

      this.messageService.showError(`${verifyError}`, 'Unable to verify');
    };

    const verificationModel = {
      _email: email,
      _verificationToken: token,
    };

    const verificationResult = this.authenticationService.verify(verificationModel);

    this.messageService.closeToasts();

    verificationResult.promise.then(successHandler, failureHandler);
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

angular.module('phpdraft.authentication').component('verifyAccount', {
  controller: VerifyAccountController,
  templateUrl: 'app/features/authentication/verifyAccount.component.html',
});
