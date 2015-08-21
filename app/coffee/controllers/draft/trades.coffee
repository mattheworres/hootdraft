class TradesController extends BaseController
  @register 'TradesController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  'subscriptionKeys',
  'api',
  'messageService'

  initialize: ->
    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and args.draft.setting_up == true
        @$scope.pageError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()
      else if args.draft? and (args.draft.in_progress == true || args.draft.complete == true)
        @_loadTradeData(args.draft.draft_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  _loadTradeData: (draft_id, args) =>
    tradesSuccess = (data) =>
      @$scope.tradesLoading = false
      @$scope.trades = data

    errorHandler = (data) =>
      @$scope.tradesLoading = false
      @$scope.tradesError = true

    @$scope.tradesLoading = args.onPageLoad? and args.onPageLoad
    @$scope.tradesError = false

    if @$scope.draftValid and not @$scope.draftLocked
      tradesPromise = @api.Trade.query({ draft_id: draft_id }, tradesSuccess, errorHandler)