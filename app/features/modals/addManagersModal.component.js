class AddManagersController {
  constructor($uibModalInstance, messageService, subscriptionKeys,
    $rootScope, $scope) {
    this.$uibModalInstance = $uibModalInstance;
    this.messageService = messageService;
    this.subscriptionKeys = subscriptionKeys;
    this.$rootScope = $rootScope;
    this.$scope = $scope;
  }

  $onInit() {
    this.editableManagers = [];

    this.addEmptyManager();
  }

  addEmptyManager() {
    this.editableManagers.push({
      manager_name: '', // eslint-disable-line camelcase
    });
  }

  removeManager(index) {
    return this.editableManagers.splice(index, 1);
  }

  addManagers() {
    const validManagers = [];
    for (const manager of Array.from(this.editableManagers)) {
      if (manager.manager_name.trim().length > 0) {
        validManagers.push(manager);
      }
    }

    const addManagerSuccess = () => {
      this.messageService.showSuccess('Managers added');
      //Need to tell index controller to reload the managers since we added them. Can also call this when deleting managers
      this.$rootScope.$broadcast(this.subscriptionKeys.updateCommishManagers, {draft: this.$rootScope.draft});
      return this.$uibModalInstance.dismiss('closed');
    };

    const addManagerError = () => this.messageService.showError('Unable to add managers');

    if (validManagers.length > 0) {
      this.api.Manager.addMultiple({draft_id: this.draftId, managers: validManagers}, addManagerSuccess, addManagerError); // eslint-disable-line camelcase
    } else {
      this.cancel();
    }
  }

  cancel() {
    this.$uibModalInstance.dismiss('closed');
  }
}

AddManagersController.$inject = [
  '$uibModalInstance',
  'messageService',
  'subscriptionKeys',
  '$rootScope',
  '$scope',
];

angular.module('phpdraft.modals').component('phpdAddManagersModal', {
  controller: AddManagersController,
  templateUrl: 'add/features/modals/addManagersModal.component.html',
  bindings: {
    draftId: '<',
  },
});
