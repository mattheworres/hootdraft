
class AdminEditUserController {
  constructor(messageService, api) {
    this.messageService = messageService;
    this.api = api;
  }

  $onInit() {
    //Just making the roles static for now. Later, we should inject them and do this dynamically
    this.roleDisplays = [
      {value: 'ROLE_COMMISH', label: 'Commissioner'},
      {value: 'ROLE_ADMIN', label: 'Site Administrator'},
    ];

    this.user.enabled = this.user.enabled === '1';
    this.userIsCommish = this.user.roles.indexOf('ROLE_COMMISH') !== -1;
    this.userIsAdmin = this.user.roles.indexOf('ROLE_ADMIN') !== -1;
  }

  userFormIsInvalid() {
    return this.actionInProgress || !this.form.$valid;
  }

  saveUser() {
    if (this.userFormIsInvalid()) {
      return;
    }

    //Here, convert from the array of objects to a comma-separated list
    const hasBoth = this.userIsCommish && this.userIsAdmin;
    let adminString = '';
    let commaString = '';
    let commishString = '';

    if (this.userIsAdmin) {
      adminString = 'ROLE_ADMIN';
    }

    if (this.userIsCommish) {
      commishString = 'ROLE_COMMISH';
    }

    if (hasBoth) {
      commaString = ',';
    }

    this.user.roles = `${adminString}${commaString}${commishString}`;

    const saveSuccess = () => {
      this.close();
    };

    const saveError = () => {
      this.messageService.showError('Unable to update user');
      this.dismiss();
    };

    this.api.User.update(this.user, saveSuccess, saveError);
  }

  cancel() {
    this.dismiss();
  }
}

AdminEditUserController.$inject = [
  'messageService',
  'api',
];

angular.module('phpdraft.modals').component('phpdAdminEditUserModal', {
  controller: AdminEditUserController,
  templateUrl: 'app/features/modals/adminEditUserModal.component.html',
  bindings: {
    user: '<',
    close: '&',
    dismiss: '&',
  },
});
