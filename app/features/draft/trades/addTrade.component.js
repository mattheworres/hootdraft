class AddTradeController {
  constructor($scope,
    $location,
    workingModalService,
    api,
    messageService,
    authenticationService,
    draftService,
    pathHelperService,
    errorService,
    lodash) {
    this.$scope = $scope;
    this.$location = $location;
    this.workingModalService = workingModalService;
    this.api = api;
    this.messageService = messageService;
    this.authenticationService = authenticationService;
    this.draftService = draftService;
    this.pathHelperService = pathHelperService;
    this.errorService = errorService;
    this.lodash = lodash;
  }

  $onInit() {
    this.tradeInProgress = false;
    this.manager1 = {id: null, index: null};
    this.manager2 = {id: null, index: null};
    this.firstManagerAssets = [];
    this.firstManagerSelectedAssets = [];
    this.secondManagerAssets = [];
    this.secondManagerSelectedAssets = [];

    this.draftService.getDraft().then(draft => {
      this.draft = draft;
      this.draftStatus = this.draftService.getStatus();

      if (draft.setting_up) {
        this.messageService.showWarning('Unable to add trade for draft: draft has not been started yet.');
      } else if (draft.complete) {
        this.messageService.showWarning('Unable to add trade for draft: draft is already completed');
      }

      if (draft.setting_up || draft.complete) {
        this.pathHelperService.sendToPreviousPath();
        this.draftError = true;
        return;
      }

      this._loadManagers();
    });
  }

  $onDestroy() {
    //Naughty, but they all get defined at the same time, so whatevs:
    if (angular.isFunction(this.manager1AssetWatchDeregister)) {
      this.manager1AssetWatchDeregister();
      this.manager2AssetWatchDeregister();
    }
  }

  _setupWatchers() {
    //Watch assets to fill selections
    this.manager1AssetWatchDeregister = this.$scope.$watch('$ctrl.firstManagerAssets', () => {
      this.firstManagerSelectedAssets = [];

      this.firstManagerAssets.forEach(asset => {
        if (asset.chosenAsset) {
          this.firstManagerSelectedAssets.push(asset);
        }
      });
    }, true);

    this.manager2AssetWatchDeregister = this.$scope.$watch('$ctrl.secondManagerAssets', () => {
      this.secondManagerSelectedAssets = [];

      this.secondManagerAssets.forEach(asset => {
        if (asset.chosenAsset) {
          this.secondManagerSelectedAssets.push(asset);
        }
      });
    }, true);
  }

  selectFirstManager(index, id, name) {
    if (this.manager2.id === id || this.manager1.id === id) return;

    this.manager1.index = index;
    this.manager1.id = id;
    this.manager1.name = name;

    this._loadManagerAssets(true, id);
  }

  selectSecondManager(index, id, name) {
    if (this.manager1.id === id || this.manager2.id === id) return;

    this.manager2.index = index;
    this.manager2.id = id;
    this.manager2.name = name;

    this._loadManagerAssets(false, id);
  }

  submitClicked() {
    if (this.tradeFormIsInvalid() === false) {
      this._saveTrade();
    }
  }

  tradeFormIsInvalid() {
    return angular.isUndefined(this.manager1.id) ||
      angular.isUndefined(this.manager2.id) ||
      angular.isUndefined(this.firstManagerSelectedAssets) ||
      angular.isUndefined(this.secondManagerSelectedAssets) ||
      (angular.isDefined(this.firstManagerSelectedAssets) && this.firstManagerSelectedAssets.length === 0) ||
      (angular.isDefined(this.secondManagerSelectedAssets) && this.secondManagerSelectedAssets.length === 0);
  }

  toggleAsset(asset) {
    asset.chosenAsset = !asset.chosenAsset;
  }

  twoManagersSelected() {
    return (this.manager1.id !== null) && (this.manager2.id !== null);
  }

  _saveTrade() {
    this.messageService.closeToasts();

    const saveSuccessHandler = () => {
      this.tradeInProgress = false;

      this.messageService.showSuccess('Trade entered');

      this.$location.path(`draft/${this.draft.draft_id}/trades`);
    };

    const saveFailure = response => {
      this.tradeInProgress = false;

      if ((angular.isDefined(response) ? response.status : 0) === 401) {
        this.messageService.showError('Unauthorized: please log in.');
        this.authenticationService.uncacheSession();
        this.$location.path('/login');
        return;
      }

      const tradeError = this.errorService.parseValidationErrorsFromResponse(response);
      this.messageService.showError(`${tradeError}`, 'Unable to enter trade');
      return;
    };

    const model = {
      draft_id: this.draft.draft_id,
      manager1_id: this.manager1.id,
      manager2_id: this.manager2.id,
      trade_assets: [],
    };

    this.firstManagerAssets.forEach(asset => {
      if (asset.chosenAsset) {
        model.trade_assets.push(asset.player_id);
      }
    });

    this.secondManagerAssets.forEach(asset => {
      if (asset.chosenAsset) {
        model.trade_assets.push(asset.player_id);
      }
    });

    return this.api.Trade.save(model, saveSuccessHandler, saveFailure);
  }

  _loadManagers() {
    this.tradeLoading = true;

    const managersSuccess = data => {
      this.managers = data.map(managerResource => { // eslint-disable-line arrow-body-style
        return {
          id: managerResource.manager_id,
          name: managerResource.manager_name,
        };
      });

      this._setupWatchers();
    };

    const errorHandler = () => {
      this.tradeLoading = false;
      this.tradeError = true;
      this.messageService.showError('Unable to load managers for trade');
    };

    this.tradeError = false;
    this.api.Manager.getManagers({draft_id: this.draft.draft_id}, managersSuccess, errorHandler);
  }

  _loadManagerAssets(isFirstManager = true, id) {
    const assetsSuccess = data => {
      const assets = [];

      data.assets.forEach(asset => {
        asset.chosenAsset = false;
        assets.push(asset);
      });

      if (isFirstManager) {
        this.firstManagerAssets = assets;
      } else {
        this.secondManagerAssets = assets;
      }

      this.assetsLoading = false;
    };

    const errorHandler = () => {
      this.assetsLoading = false;

      this.messageService.showError('Unable to load assets for manager');
    };

    this.assetsLoading = true;
    this.api.Trade.getAssets({draft_id: this.draft.draft_id, manager_id: id}, assetsSuccess, errorHandler);
  }
}

AddTradeController.$inject = [
  '$scope',
  '$location',
  'workingModalService',
  'api',
  'messageService',
  'authenticationService',
  'draftService',
  'pathHelperService',
  'errorService',
  'lodash',
];

angular.module('phpdraft.draft').component('phpdAddTrade', {
  controller: AddTradeController,
  templateUrl: 'app/features/draft/trades/addTrade.component.html',
});
