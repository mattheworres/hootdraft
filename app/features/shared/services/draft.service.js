const DRAFT_ERROR_MESSAGE = 'We seem to be having trouble loading this draft, possibly due to network connectivity issues';

class DraftService {
  constructor($uibModal, $sessionStorage, $routeParams, api, lodash,
    $interval, messageService, subscriptionKeys, $rootScope, $q, errorService) {
    this.$uibModal = $uibModal;
    this.$sessionStorage = $sessionStorage;
    this.$routeParams = $routeParams;
    this.api = api;
    this.lodash = lodash;
    this.$interval = $interval;
    this.messageService = messageService;
    this.subscriptionKeys = subscriptionKeys;
    this.$rootScope = $rootScope;
    this.$q = $q;
    this.errorService = errorService;

    this.activeComponents = 0;
    this.failedConnectionTries = 0;
    this.draftId = this.$routeParams.draft_id;
    this.draft = null;
    this.lastDraftCounter = null;
    this.draftError = false;
    this.timerInstance = null;
    this.apiCallInProgress = false;
    this.pollingIntervalMs = 2750;
    this.draftStatus = {
      loading: false,
      error: false,
      valid: false,
      locked: false,
      badConnection: false,
    };

    this.getStatus = this.getStatus.bind(this);
    this._draftIntervalHandler = this._draftIntervalHandler.bind(this);
    this._draftErrorHandler = this._draftErrorHandler.bind(this);

    this.deregister = this.$rootScope.$on(this.subscriptionKeys.routeHasDraft, (event, args) => {
      const {hasDraft} = args;
      const draftIdChanged = this.draftId !== this.$routeParams.draft_id;

      if (hasDraft === false || draftIdChanged) {
        this.activeComponents = 0;
        this._stopPollingForData();
      } else {
        this.$rootScope.$broadcast(this.subscriptionKeys.draftCounterHasChanged, {draft: this.draft, status: this.draftStatus});
      }
    });
  }

  //Method components use in order to grab a reference to the current (by the route params) draft
  /**
   * Method components use in order to grab a reference to the current (by the route params) draft. Runs a single time (to completion once, short circuited for multiple component calls)
   */
  getDraft() {
    this.activeComponents++;
    this.defer = this.$q.defer();

    //If we've already received the draft, we can stop here and pass back the draft reference for this component call
    if (this.draft !== null && this.draft.draft_id === this.$routeParams.draft_id) {
      this.defer.resolve(this.draft);
      return this.defer.promise;
    }

    //If there's already an api call in progress, lets re-check every second to see if the draft has been populated before giving up
    if (this.apiCallInProgress) {
      let retryNumber = 0;
      this.retryInterval = this.$interval(() => {
        retryNumber++;

        if (angular.isDefined(this.draft)) {
          this.defer.resolve(this.draft);
          this.$interval.cancel(this.retryInterval);
          this.retryInterval = null;
          return;
        }

        if (retryNumber === 5) {
          this.messageService.closeToasts();
          this.messageService.showWarning(DRAFT_ERROR_MESSAGE);
          this.defer.reject({error: true, message: DRAFT_ERROR_MESSAGE});
        }
      }, 1500);

      return this.defer.promise;
    }

    //From here the assumption is the remaining code in this method is only run once, the very first time

    //Set this flag to ensure we're not making concurrent requests to the API as multiple components mount at the same time
    this.apiCallInProgress = true;
    this.draftStatus.loading = true;
    //Get heavy draft info from API if this is the first call to the API
    const getDraftData = this.draft === null || this.draftId !== this.$routeParams.draft_id;
    //Ensure the draft ID we're using is correct
    this.draftId = this.$routeParams.draft_id;

    this._loadDraftFromApi(this.draftId, getDraftData).then(draftInMemory => {
      this.apiCallInProgress = false;
      this.draftStatus.loading = false;
      this.draftStatus.error = false;
      this.draftStatus.valid = true;
      this.draftStatus.locked = draftInMemory.is_locked;
      this.$rootScope.$broadcast(this.subscriptionKeys.draftCounterHasChanged, {draft: draftInMemory, status: this.draftStatus});

      if (this.draft === null) {
        this.draft = {};
      }

      //Perform a merge so the object remains intact and Angular's watchers dont lose it
      this.lodash.merge(this.draft, draftInMemory);
      this.lastDraftCounter = this.draft.draft_counter;

      //Complete drafts are the only kind of draft that do not poll for updates.
      if (this.draft.complete === true) {
        this._stopPollingForData();
      } else {
        this._startPollingForData();
      }

      if (this.draft.is_locked === true) {
        this._stopPollingForData();
        this.draftStatus.valid = false;
        this.$rootScope.$broadcast(this.subscriptionKeys.showPasswordModal);
      }

      this.defer.resolve(this.draft);

    }, this._draftErrorHandler);

    return this.defer.promise;
  }

  // Get the draft status object for loading, error and valid states
  getStatus() {
    return this.draftStatus;
  }

  //Method components use to indicate they no longer require polling updates for draft data
  componentOnDestroy() {
    this.activeComponents--;

    if (this.activeComponents <= 0 && this.timerInstance !== null) {
      this.activeComponents = 0;
      this.dependentDataCallbacks = [];
      this._stopPollingForData();
    }
  }

  startDraft(draftId) {
    if (this.draftId !== draftId) {
      this.draftId = draftId;
    }

    const startSuccess = () => {
      this.messageService.showSuccess('Draft started');
      this.draft.setting_up = false; // eslint-disable-line camelcase
      this.draft.in_progress = true; // eslint-disable-line camelcase
      this.$rootScope.$broadcast(this.subscriptionKeys.draftCounterHasChanged, {draft: this.draft, status: this.draftStatus});
    };

    const startError = response => {
      let startErrors = '';
      if ((response.data === null ? null : response.data.errors) !== null) {
        startErrors = this.errorService.joinErrorsForToastDisplay(response.data.errors);
      }

      this.messageService.showError(`Unable to start draft    ${startErrors}`);
    };

    return this.api.Draft.updateStatus({draft_id: draftId, status: 'in_progress'}, startSuccess, startError); // eslint-disable-line camelcase
  }

  resetDraft(draftId) {
    if (this.draftId !== draftId) {
      this.draftId = draftId;
    }

    const resetSuccess = () => {
      this.messageService.showSuccess('Draft reset');
      this.draft.setting_up = true; // eslint-disable-line camelcase
      this.draft.in_progress = false; // eslint-disable-line camelcase
      this.$rootScope.$broadcast(this.subscriptionKeys.draftCounterHasChanged, {draft: this.draft, status: this.draftStatus});
      this.$location.path(`/draft/${draftId}`);
    };

    const resetError = response => {
      let restartErrors = '';
      if ((response.data === null ? null : response.data.errors) !== null) {
        restartErrors = this.errorService.joinErrorsForToastDisplay(response.data.errors);
      }

      this.messageService.showError(`Unable to reset draft    ${restartErrors}`);
    };

    return this.api.Draft.updateStatus({draft_id: draftId, status: 'undrafted'}, resetSuccess, resetError); // eslint-disable-line camelcase
  }

  deleteDraft(draftId) {
    if (this.draftId !== draftId) {
      this.draftId = draftId;
    }

    const deleteSuccess = () => {
      this.messageService.showSuccess('Draft deleted');
      this.$location.path('/home');
    };

    const deleteError = () => {
      this.messageService.showError('Unable to delete draft');
    };

    return this.api.Draft.delete({draft_id: draftId}, deleteSuccess, deleteError); // eslint-disable-line camelcase
  }

  /*
  * Private Methods
  */

  //Private method to call the API
  _loadDraftFromApi(draftId, getDraftData = false) {
    return this.api.Draft.get({id: draftId, get_draft_data: getDraftData}).$promise; // eslint-disable-line camelcase
  }

  //Private method that begins interval to call for draft updates
  _startPollingForData() {
    if (this.timerInstance !== null || !this.draft.in_progress || this.draft.is_locked) {
      return;
    }

    this.timerInstance = this.$interval(this._draftIntervalHandler, this.pollingIntervalMs);
  }

  //Private method to stop the interval for draft updates
  _stopPollingForData() {
    if (this.timerInstance === null) {
      return;
    }

    this.$interval.cancel(this.timerInstance);
    this.timerInstance = null;
  }

  //Private method to handle the periodic update of the draft; notifies all components when the counter changes via callbacks
  _draftIntervalHandler() {
    if (angular.isDefined(this.defer)) {
      this.defer = null;
    }
    //If the draftId has changed, we need to get all draft data, which is a heavy call
    const getDraftData = this.draftId !== this.$routeParams.draft_id;

    this.draftId = this.$routeParams.draft_id;

    this._loadDraftFromApi(this.draftId, getDraftData).then(draftInMemory => {
      this.draftStatus.badConnection = false;
      this.failedConnectionTries = 0;
      this.draftStatus.locked = draftInMemory.is_locked;

      //Either the current one has changed or we need to get a new draft, so notify subscribers that the counter has changed
      if (this.lastDraftCounter !== draftInMemory.draft_counter || getDraftData) {
        this.$rootScope.$broadcast(this.subscriptionKeys.draftCounterHasChanged, {draft: draftInMemory, status: this.draftStatus});
      }

      this.lastDraftCounter = draftInMemory.draft_counter;

      this.lodash.merge(this.draft, draftInMemory);
      this.draftStatus.locked = this.draft.is_locked;

      if (this.draft.complete === true) {
        this._stopPollingForData();
      }
    }, this._draftErrorHandler);
  }

  _draftErrorHandler() {
    this.failedConnectionTries++;

    this.apiCallInProgress = false;
    this.draftStatus.loading = false;

    if (this.activeComponents > 0) {
      this.messageService.closeToasts();
      this.messageService.showWarning(DRAFT_ERROR_MESSAGE);

      if (this.failedConnectionTries > 4) {
        this.draftStatus.error = false;
        this.draftStatus.valid = false;
        this.draftStatus.badConnection = true;
        this._stopPollingForData();
      }
    }

    if (angular.isDefined(this.defer) && this.defer !== null) {
      this.defer.reject({error: true, message: DRAFT_ERROR_MESSAGE});
      this.defer = null;
    }
  }
}

DraftService.$inject = [
  '$uibModal',
  '$sessionStorage',
  '$routeParams',
  'api',
  'lodash',
  '$interval',
  'messageService',
  'subscriptionKeys',
  '$rootScope',
  '$q',
];

angular.module('phpdraft.shared').service('draftService', DraftService);
