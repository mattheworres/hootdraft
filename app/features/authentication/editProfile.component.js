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
      this.savedEmail = this.userProfile.email;
      this.loading = false;
    };

    const errorHandler = () => {
      this.messageService.showError('Unable to load user profile');
      this.loading = false;
    };

    this.api.Authentication.getProfile({}, loadSuccess, errorHandler);
  }

  // The server compares addresses without regard to case. No model value means the field holds an invalid address.
  emailChanged() {
    const {email} = this.userProfile;

    return angular.isString(email) &&
      angular.isString(this.savedEmail) &&
      email.toLowerCase() !== this.savedEmail.toLowerCase();
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
          this.messageService.showInfo('Your email was updated, so we logged you out. To log in again, click the link we emailed to your new address to verify it. See you soon!');
          this.$location.path('/home');
        } else {
          this.messageService.showInfo('Since you changed your password, we had to log you out and log you back in again. But no biggie - just enter your new password now to login again.');
          this.$location.path('/login');
        }

        // The session has ended: do not cache the name again or send the user back to a page that needs a login
        return;
      }

      if (this.$sessionStorage.userName !== this.userProfile.name) {
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
