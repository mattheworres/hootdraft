class AddManagersModalController {
  constructor(messageService, api, $rootScope, subscriptionKeys) {
    this.messageService = messageService;
    this.api = api;
    this.$rootScope = $rootScope;
    this.subscriptionKeys = subscriptionKeys;
  }

  $onInit() {
    //this.draftId = this.resolve.draftId;
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
      this.$rootScope.$broadcast(this.subscriptionKeys.updateCommishManagers, {draft: this.draft});
      this.close();
    };

    const addManagerError = () => this.messageService.showError('Unable to add managers');

    if (validManagers.length > 0) {
      this.api.Manager.addMultiple({draft_id: this.draft.draft_id, managers: validManagers}, addManagerSuccess, addManagerError); // eslint-disable-line camelcase
    } else {
      this.dismiss();
    }
  }

  cancel() {
    this.close();
  }
}

AddManagersModalController.$inject = [
  'messageService',
  'api',
  '$rootScope',
  'subscriptionKeys',
];

angular.module('phpdraft.modals').component('phpdAddManagersModal', {
  controller: AddManagersModalController,
  templateUrl: 'app/features/modals/addManagersModal.component.html',
  bindings: {
    draft: '<',
    close: '&',
    dismiss: '&',
  },
});
