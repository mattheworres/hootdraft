class EditProfileController {
  constructor($sessionStorage, $location,
    authenticationService, api, workingModalService, messageService,
    subscriptionKeys, errorService, pathHelperService, lodash) {
    this.$sessionStorage = $sessionStorage;
    this.$location = $location;
    this.authenticationService = authenticationService;
    this.api = api;
    this.workingModalService = workingModalService;
    this.messageService = messageService;
    this.subscriptionKeys = subscriptionKeys;
    this.errorService = errorService;
    this.pathHelperService = pathHelperService;
    this.lodash = lodash;

    this.loading = true;
  }

  $onInit() {
    this.showPassword = false;
    this.userProfile = {};
    this.loading = true;

    this.loadUserProfileData();
  }

  loadUserProfileData() {
    const loadSuccess = data => {
      this.lodash.merge(this.userProfile, data);
      this.loading = false;
    };

    const errorHandler = () => {
      this.messageService.showError('Unable to load user profile');
      this.loading = false;
    };

    this.api.Authentication.getProfile({}, loadSuccess, errorHandler);
  }

  passwordInputType() {
    return this.showPassword
      ? 'text'
      : 'password';
  }

  submitClicked() {
    if (this.form.$valid) {
      this.update();
    }
  }

  profileFormIsInvalid() {
    return this.updateInProgress || !this.form.$valid;
  }

  update() {
    this.workingModalService.openModal();

    const updateModel = {
      _email: this.userProfile.email,
      _name: this.userProfile.name,
      _password: this.userProfile.password,
      _newPassword: this.userProfile.newPassword,
      _newConfirmedPassword: this.userProfile.newConfirmedPassword,
    };

    this.updateInProgress = true;

    this.messageService.closeToasts();

    const updateSuccessHandler = response => {
      this.updateInProgress = false;
      this.workingModalService.closeModal();
      this.form.$setPristine();

      if (response.invalidateLogin === true) {
        this.authenticationService.uncacheSession();

        if (response.sendEmail === true) {
          this.messageService.showInfo('Your email was updated. In order to login again you must verify your email address. An email has been sent to that account to activate it. You will be logged out for now. See you soon!');
          this.$location.path('/home');
        }

        this.messageService.showInfo('Since you changed your password, we had to log you out and log you back in again. But no biggie - just enter your new password now to login again.');
        this.$location.path('/login');

      }

      if (this.$sessionStorage.user_name !== this.userProfile.name) {
        this.authenticationService.cacheName(this.userProfile.name);
      }

      this.messageService.showInfo('User profile information updated!');
      this.pathHelperService.sendToPreviousPath();
    };

    const updateFailureHandler = response => {
      this.updateInProgress = false;
      this.workingModalService.closeModal();

      const updateError = this.errorService.parseValidationErrorsFromResponse(response);

      this.messageService.showError(`${updateError}`, 'Unable to update your user profile');
    };

    this.api.Authentication.setProfile(updateModel, updateSuccessHandler, updateFailureHandler);
  }
}

EditProfileController.$inject = [
  '$sessionStorage',
  '$location',
  'authenticationService',
  'api',
  'workingModalService',
  'messageService',
  'subscriptionKeys',
  'errorService',
  'pathHelperService',
  'lodash',
];

angular.module('phpdraft.authentication').component('phpdEditProfile', {
  controller: EditProfileController,
  templateUrl: 'app/features/authentication/editProfile.component.html',
});
