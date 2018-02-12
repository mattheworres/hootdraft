class CommishManagersController {
  constructor($scope, $rootScope, $q, $routeParams, messageService,
    subscriptionKeys, draftModalService, api, $loading,
    $timeout, confirmActionService) {
    this.$scope = $scope;
    this.$rootScope = $rootScope;
    this.$q = $q;
    this.$routeParams = $routeParams;
    this.messageService = messageService;
    this.subscriptionKeys = subscriptionKeys;
    this.draftModalService = draftModalService;
    this.api = api;
    this.$loading = $loading;
    this.$timeout = $timeout;
    this.confirmActionService = confirmActionService;
  }

  $onInit() {
    this.editableManagers = [];
    this.showCountDown = false;
    this.countDown = 5;

    this.deregisterUpdateManagers = this.$rootScope.$on(this.subscriptionKeys.updateCommishManagers, (event, args) => {
      const {draft} = args;
      console.debug('We got word to update the commish');
      this._reloadEditableManagers(draft.draft_id, draft.commish_editable);
    });

    //TODO: is this necessary?
    console.debug('Updating the commish on init');
    this._reloadEditableManagers();
  }

  $onDestroy() {
    this.deregisterUpdateManagers();
  }

  //# Slip Events
  reorderManagers(event, spliceIndex, originalIndex) {
    const manager = this.editableManagers[originalIndex];
    this.editableManagers.splice(originalIndex, 1);
    this.editableManagers.splice(spliceIndex, 0, manager);

    this._saveManagerOrder();

    return true;
  }

  beforeSwipe(event) {
    event.preventDefault();
  }

  beforeWait(event) {
    if (event.target.className.indexOf('fa') === -1) {
      event.preventDefault();
    }
  }

  //# Event Handlers
  openAddManagerModal() {
    this._resetManagerEdits();
    this.draftModalService.showAddManagersModal(this.$routeParams.draft_id);
  }

  deleteManager(index) {
    if (this.isEditActive) {
      return;
    }

    const originalManager = this.editableManagers[index];
    this.editableManagers.splice(index, 1);

    const saveSuccess = data => {
      this.editableManagers = data.managers;
      this._setViewPropertyOnManagers(true);

      this._resetManagerEdits();
    };

    const errorHandler = () => {
      this.messageService.showError('Unable to delete manager');
      this.editableManagers.splice(index, 0, originalManager);
      this._reorderInMemoryManagers();
      this._resetManagerEdits();
    };

    this.api.Manager.delete({draft_id: this.$routeParams.draft_id, manager_id: originalManager.manager_id}, saveSuccess, errorHandler); // eslint-disable-line camelcase
  }

  enableManagerEdit(index) {
    if (this.isEditActive) {
      return;
    }

    const manager = this.editableManagers[index];
    manager.isBeingEdited = true;
    this.isEditActive = true;
    this._editedManagerIndex = index;
    this._editedManagerOriginalName = manager.manager_name;
  }

  cancelManagerEdit(index) {
    const manager = this.editableManagers[index];
    manager.isBeingEdited = false;
    manager.managerSaving = false;
    this.isEditActive = false;
    manager.manager_name = this._editedManagerOriginalName; // eslint-disable-line camelcase
    this._editedManagerIndex = null;
  }

  saveManager(index) {
    const manager = this.editableManagers[index];
    manager.managerSaving = true;

    const updateSuccess = () => {
      this.isEditActive = false;
      manager.isBeingEdited = false;
      manager.managerSaving = false;
      return this.messageService.showSuccess(`${manager.manager_name} updated!`);
    };

    const errorHandler = () => {
      manager.managerSaving = false;
      return this.messageService.showError('Unable to update manager');
    };

    return this.api.Manager.update({draft_id: this.$routeParams.draft_id, manager_id: manager.manager_id, name: manager.manager_name}, updateSuccess, errorHandler); // eslint-disable-line camelcase
  }

  randomizeDraftOrder() {
    const title = 'Randomize the draft order?';
    const message = 'Want to automatically randomize your draft\'s selection order? Great! Make sure everyone\'s watching your screen to see where they end up!';
    const iconClass = 'fa-random';
    const confirmButtonText = 'Yes, Randomize this draft!';

    return this.confirmActionService.showConfirmationModal(message, this._randomizationProcess, title, iconClass, confirmButtonText);
  }

  //# Private methods
  _randomizationProcess() {
    this.commishManagersLoading = true;
    this.randomizeInProgress = true;
    let randomizationMinimumTimeMet = false;
    let managerSaveSuccess = false;

    this._setViewPropertyOnManagers(false);

    this._shuffleDraftOrder();

    const saveResult = this._saveManagerOrder();

    const randomizationPresentation = () => {
      this.commishManagersLoading = false;
      this.countDown = 3;
      this.showCountDown = true;
      let stepCount = 1;
      const totalManagers = this.editableManagers.length;
      let currentManagerIndex = 0;
      this.intervalMilliseconds = 1100;
      this.shouldContinue = true;

      this.timingLoop = () => {
        if (this.shouldContinue === false) {
          return;
        }

        if (stepCount < 3) {
          this.countDown--;
          stepCount++;
          this.$timeout(this.timingLoop, this.intervalMilliseconds);
          return;
        }

        this.showCountDown = false;

        this.editableManagers[currentManagerIndex].shown = true;

        //Set the grading interval for the next loop
        this.intervalMilliseconds = (() => {
          switch (currentManagerIndex) {
            case (currentManagerIndex < 2): return 2500;
            case currentManagerIndex === 2: return 1800;
            case (currentManagerIndex === 3) || (currentManagerIndex === 4): return 600;
            case (currentManagerIndex >= 5): return 400;
          }

          return null;
        })();

        if ((currentManagerIndex + 1) === totalManagers) {
          this.$timeout(() => {
            this.randomizeInProgress = false;
          }, 750);
          this.shouldContinue = false;
        } else {
          currentManagerIndex++;
        }

        if (this.shouldContinue) {
          this.$timeout(this.timingLoop, this.intervalMilliseconds);
        }
      };

      this.$timeout(this.timingLoop, this.intervalMilliseconds);
    };

    //Holding the loading display for a second or two so it looks like it's doing "heavy" lifting...
    this.$timeout(() => {
      randomizationMinimumTimeMet = true;

      if (managerSaveSuccess === true) {
        randomizationPresentation();
      }
    }, 1750);

    managerSaveSuccess = () => {
      managerSaveSuccess = true;

      if (randomizationMinimumTimeMet === true) {
        randomizationPresentation();
      }
    };

    const managerSaveError = () => {
      this.commishManagersLoading = false;
      this.managersError = true;
      this.messageService.showError('Unable to randomize draft order - error while saving managers.');
    };

    saveResult.promise.then(managerSaveSuccess, managerSaveError);
  }

  _reloadEditableManagers(draftId, draftCommishEditable) {
    const commishManagersSuccess = data => {
      this.commishManagersLoading = false;
      this.editableManagers = data;

      //Set visibility flag by default
      this._setViewPropertyOnManagers(true);

      this._resetManagerEdits();
    };

    const managersError = () => {
      this.managersLoading = false;
      this.managersError = true;
      this.messageService.showError('Unable to load managers');
    };

    if ((this.$routeParams.draft_id !== null) && draftCommishEditable) {
      this.editableManagers = [];
      this.api.Manager.commishGet({draft_id: draftId}, commishManagersSuccess, managersError);
    }
  }

  _saveManagerOrder() {
    const result = this.$q.defer();

    const reorderSuccess = () => {
      this.$loading.finish('saving_order');
      this._reorderInMemoryManagers();
      return result.resolve();
    };

    const reorderError = () => {
      this.$loading.finish('saving_order');
      this.messageService.showError('Unable to reorder managers');
      return result.reject();
    };

    this.$loading.start('saving_order');
    const managerIds = [];
    const draft_order = 1;
    for (let manager of Array.from(this.editableManagers)) {
      managerIds.push(manager.manager_id);
    }

    this.api.Manager.reorder({draft_id: this.$routeParams.draft_id, ordered_manager_ids: managerIds}, reorderSuccess, reorderError); // eslint-disable-line camelcase

    return result;
  }

  _resetManagerEdits() {
    this.isEditActive = false;
    if ((this._editedManagerOriginalName != null) && (this._editedManagerOriginalName.length > 0) && (this._editedManagerIndex != null) && (this._editedManagerIndex !== null)) {
      return this.cancelManagerEdit(this._editedManagerIndex);
    }
  }

  _reorderInMemoryManagers() {
    let draft_order = 1;
    for (let manager of Array.from(this.editableManagers)) {
      manager.draft_order = draft_order;
      draft_order++;
    }
  }

  //Fisher–Yates shuffle algorithm - from http://stackoverflow.com/a/20791049/324527
  _shuffleDraftOrder() {
    if (this.editableManagers.length === 0) {
      return;
    }

    let m = this.editableManagers.length;

    //While there remain elements to shuffle
    return (() => {
      const result = [];
      while (m) {
        //Pick a remaining element…
        const i = Math.floor(Math.random() * m--);

        //And swap it with the current element.
        const manager = this.editableManagers[m];
        this.editableManagers.splice(m, 1);
        result.push(this.editableManagers.splice(i, 0, manager));
      }
      return result;
    })();
  }

  _setViewPropertyOnManagers(viewSetting) {
    Array.from(this.editableManagers).map(manager => {
      manager.shown = viewSetting;
    });
  }
}

CommishManagersController.$inject = [
  '$scope',
  '$rootScope',
  '$q',
  '$routeParams',
  'messageService',
  'subscriptionKeys',
  'draftModalService',
  'api',
  '$loading',
  '$timeout',
  'confirmActionService',
];

angular.module('phpdraft.draft').directive('phpdCommishManagers', () =>
  ({
    restrict: 'E',
    controller: CommishManagersController,
    templateUrl: 'app/features/draft/index/commishManagers.directive.html',
    scope: {
      editableManagers: '=',
      draft: '=',
    },
  })
);
