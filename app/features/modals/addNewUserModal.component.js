class AddNewUserModalController {
  constructor(messageService, api, subscriptionKeys, errorService, $scope) {
    this.messageService = messageService;
    this.api = api;
    this.subscriptionKeys = subscriptionKeys;
    this.errorService = errorService;
    this.$scope = $scope;
  }

  $onInit() {
    this.inviteInProgress = false;

    this.newUser = {
      email: '',
      name: '',
      message: '',
    };
  }

  inviteUser() {
    if (this.formIsInvalid()) return;

    this.inviteInProgress = true;

    const inviteSuccess = () => {
      this.inviteInProgress = false;
      this.messageService.showSuccess(`${this.newUser.name} should get an email shortly inviting them to join. Good job!`);

      this.$scope.$parent.$close();
    };

    const inviteError = response => {
      const errors = this.errorService.parseValidationErrorsFromResponse(response);
      this.inviteInProgress = false;
      this.messageService.showError(`Unable to invite user: ${errors}`);
      this.dismiss();
    };

    this.api.Authentication.invite(this.newUser, inviteSuccess, inviteError);
  }

  formIsInvalid() {
    return this.inviteInProgress || !this.form.$valid;
  }
}

AddNewUserModalController.$inject = [
  'messageService',
  'api',
  'subscriptionKeys',
  'errorService',
  '$scope',
];

angular.module('phpdraft.modals').component('phpdAddNewUserModal', {
  controller: AddNewUserModalController,
  templateUrl: 'app/features/modals/addNewUserModal.component.html',
  bindings: {
    close: '&',
    dismiss: '&',
  },
});
