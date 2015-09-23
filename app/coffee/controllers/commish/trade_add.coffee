class AddTradeController extends BaseController
  @register 'AddTradeController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  '$q',
  '$location',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService',
  'authenticationService'

  initialize: ->
    @tradeInProgress = false
    @$scope.firstManagerAssets = []
    @$scope.firstManagerSelectedAssets = []
    @$scope.secondManagerAssets = []
    @$scope.secondManagerSelectedAssets = []
    @loadedManagersOnce = false

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and (args.draft.setting_up == true || args.draft.complete == true)
        if args.draft.setting_up
          @messageService.showWarning "Unable to add trade for draft: draft has not been started yet."
        else if args.draft.complete
          @messageService.showWarning "Unable to add trade for draft: draft is already completed"

        @deregister()
        @sendToPreviousPath()
        @draftError = true
      else
        if not @loadedManagersOnce
          @_loadManagers()

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    #Watch dropdowns to fill assets
    @$scope.$watch ( =>
      @$scope.manager1_id
    ), =>
      if @$scope.manager1_id != undefined
        @_loadManagerAssets(true, @$scope.manager1_id)
        @_disableManagerInOtherDropdown(@$scope.secondManagers, @$scope.manager1_id)

    @$scope.$watch ( =>
      @$scope.manager2_id
    ), =>
      if @$scope.manager2_id != undefined
        @_loadManagerAssets(false, @$scope.manager2_id)
        @_disableManagerInOtherDropdown(@$scope.firstManagers, @$scope.manager2_id)

    #Watch assets to fill selections
    @$scope.$watch ( =>
      @$scope.firstManagerAssets
    ), =>
      @$scope.firstManagerSelectedAssets = []
      for asset in @$scope.firstManagerAssets
        if asset.chosenAsset
          @$scope.firstManagerSelectedAssets.push asset
    , true

    @$scope.$watch ( =>
      @$scope.secondManagerAssets
    ), =>
      @$scope.secondManagerSelectedAssets = []
      for asset in @$scope.secondManagerAssets
        if asset.chosenAsset
          @$scope.secondManagerSelectedAssets.push asset
    , true

  submitClicked: ->
    if not @tradeFormIsInvalid()
      @_saveTrade()

  tradeFormIsInvalid: ->
    return not @$scope.manager1_id? or not @$scope.manager2_id? or @$scope.firstManagerAssets.length == 0 or @$scope.secondManagerAssets.length == 0 or @$scope.firstManagerSelectedAssets.length == 0 or @$scope.secondManagerSelectedAssets.length == 0

  toggleAsset: (asset) ->
    asset.chosenAsset = not asset.chosenAsset
    return

  twoManagersSelected: ->
    @$scope.manager1_id? and @$scope.manager2_id?

  _disableManagerInOtherDropdown: (managers, manager_id) ->
    for manager in managers
      manager.disabled = manager.manager_id == manager_id

  _saveTrade: ->
    @messageService.closeToasts()

    saveSuccessHandler = (response) =>
      @tradeInProgress = false

      @messageService.showSuccess "Trade entered"
      
      @$location.path "draft/#{@$routeParams.draft_id}/trades"

    saveFailure = (response) =>
      @tradeInProgress = false

      if response?.status is 400
        tradeError = response.data?.errors?.join('\n')
      else if response?.status is 401
        @messageService.showError "Unauthorized: please log in."
        @authenticationService.uncacheSession()
        @$location.path '/login'
      else
        tradeError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{tradeError}", 'Unable to enter trade'

    model =
      draft_id: @$routeParams.draft_id
      manager1_id: @$scope.manager1_id
      manager2_id: @$scope.manager2_id
      trade_assets: []

    for asset in @$scope.firstManagerAssets
      if asset.chosenAsset
        model.trade_assets.push asset.player_id

    for asset in @$scope.secondManagerAssets
      if asset.chosenAsset
        model.trade_assets.push asset.player_id

    @api.Trade.save(model, saveSuccessHandler, saveFailure)

  _loadManagers: ->
    @loadedManagersOnce = true
    @$scope.tradeLoading = true

    managersSuccess = (data) =>
      @$scope.firstManagers = data
      @$scope.secondManagers = data

    errorHandler = (response) =>
      @$scope.tradeLoading = false
      @$scope.tradeError = true
      @messageService.showError "Unable to load managers for trade"

    @$scope.tradeError = false
    @api.Manager.getManagers({draft_id: @$routeParams.draft_id}, managersSuccess, errorHandler)

  _loadManagerAssets: (isFirstManager = true, manager_id) ->
    assetsSuccess = (data) =>
      assets = []
      for asset in data.assets
        asset.chosenAsset = false
        assets.push(asset)

      if isFirstManager
        @$scope.firstManagerAssets = assets
      else
        @$scope.secondManagerAssets = assets

      @$scope.assetsLoading = false

    errorHandler = (response) =>
      @$scope.assetsLoading = false

      @messageService.showError "Unable to load assets for manager"

    @$scope.assetsLoading = true
    console.log manager_id
    @api.Trade.getAssets({draft_id: @$routeParams.draft_id, manager_id: manager_id}, assetsSuccess, errorHandler)




