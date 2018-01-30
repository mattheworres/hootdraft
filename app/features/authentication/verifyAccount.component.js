class VerifyAccountController {
  constructor($q, $routeParams, messageService,
    workingModalService, authenticationService, $location,
    $timeout) {
    this.$q = $q;
    this.$routeParams = $routeParams;
    this.messageService = messageService;
    this.workingModalService = workingModalService;
    this.authenticationService = authenticationService;
    this.$location = $location;
    this.$timeout = $timeout;
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
      let verifyError;
      this.workingModalService.closeModal();

      this.showErrorInformation = true;

      if (angular.isDefined(response) && angular.isDefined(response.data) && response.data.status === 400) {
        verifyError = angular.isDefined(response.data.data) &&
          angular.isDefined(response.data.data.errors)
          ? response.data.data.errors.join('\n')
          : 'Unknown 400 error';
      } else {
        verifyError = `Whoops! We hit a snag - looks like it's on our end (${response.data.status})`;
      }

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
];

angular.module('phpdraft.authentication').component('verifyAccount', {
  controller: VerifyAccountController,
  templateUrl: 'app/features/authentication/verifyAccount.component.html',
});
