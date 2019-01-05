class UsersController {
  constructor($scope,
    $uibModal,
    api,
    messageService,
    confirmActionService,
    workingModalService) {
    this.$scope = $scope;
    this.$uibModal = $uibModal;
    this.api = api;
    this.messageService = messageService;
    this.confirmActionService = confirmActionService;
    this.workingModalService = workingModalService;
  }

  $onInit() {
    this.users = [];
    this.roles = [];

    this._loadUsers();
  }

  _loadUsers() {
    const userInitSuccess = response => {
      this.users = response.users;
      this.roles = response.roles;
    };

    const userInitError = () => {
      this.messageService.showError('Unable to load users');
    };

    this.users = this.api.User.getAll({}, userInitSuccess, userInitError);
  }

  editUser(user, userIndex) {
    this.editedUser = user;
    this.editedUserIndex = userIndex;

    this.modalInstance = this.$uibModal.open({
      template: `<phpd-admin-edit-user-modal
        user="::$resolve.user"
        dismiss="$dismiss()"
        close="$close()">`,
      controller: angular.noop,
      resolve: {
        user: () => user,
      },
    });

    this.modalInstance.result.then(() => {
      this._loadUsers();
    });
  }

  deleteUser(user, itemIndex) {
    const title = `Delete ${user.name}?`;
    const message = `Are you sure you want to delete ${user.name}? This action cannot be undone.`;
    const iconClass = 'fa-trash';
    const confirmButtonText = 'Yes, Delete User';

    const deleteUserHandler = () => {
      const deleteSuccess = () => {
        this.workingModalService.closeModal();
        this.users.splice(itemIndex, 1);
        this.messageService.showSuccess('User deleted');
      };

      const deleteError = () => {
        this.workingModalService.closeModal();
        this.messageService.showError('Unable to delete user');
      };

      this.workingModalService.openModal();
      this.api.User.delete({id: user.id}, deleteSuccess, deleteError);
    };

    this.confirmActionService.showConfirmationModal(message, deleteUserHandler, title, iconClass, confirmButtonText);
  }
}

UsersController.$inject = [
  '$scope',
  '$uibModal',
  'api',
  'messageService',
  'confirmActionService',
  'workingModalService',
];

angular.module('phpdraft.admin').component('phpdUsers', {
  controller: UsersController,
  templateUrl: 'app/features/admin/users.component.html',
});
