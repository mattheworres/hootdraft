class DraftIndexController extends BaseController
  @register 'DraftIndexController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  'subscriptionKeys',
  'api',
  'messageService'

  initialize: ->
    @$scope.selectedDraftRound = 1

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and args.draft.setting_up == true
        @_loadSettingUpData(args.draft.draft_id, args)
      else if args.draft? and args.draft.in_progress == true
        @_loadInProgressData(args.draft.draft_id, args)
      else if args.draft? and args.draft.complete == true
        @$scope.pagerItemTally = @$rootScope.draft.draft_rounds * 10
        @_loadCompletedData(args.draft.draft_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    @$scope.$watch ( =>
      @$scope.selectedDraftRound
    ), =>
      if @$scope.draft != undefined and @$scope.draftValid and not @$scope.draftLocked
        @_loadCompletedData(@$routeParams.draft_id)

  _loadSettingUpData: (draft_id, args) =>
    managersSuccess = (data) =>
      @$scope.managersLoading = false
      @$scope.managers = data

    managersError = (response) =>
      @$scope.managersLoading = false
      @$scope.managersError = true
      @messageService.showError "Unable to load managers"

    @$scope.managersLoading = args.onPageLoad? and args.onPageLoad
    @$scope.managersError = false

    if @$scope.draftValid and not @$scope.draftLocked
      managersPromise = @api.Manager.getManagers({ draft_id: draft_id }, managersSuccess, managersError)
    #Round times?

  _loadInProgressData: (draft_id, args) =>
    lastSuccess = (data) =>
      @$scope.lastLoading = false
      @$scope.lastFivePicks = data

    nextSuccess = (data) =>
      @$scope.nextLoading = false
      @$scope.nextFivePicks = data

    lastErrorHandler = (data) =>
      @$scope.lastLoading = false
      @$scope.lastError = true

    nextErrorHandler = (data) =>
      @$scope.nextLoading = false
      @$scope.nextError = true

    @$scope.lastLoading = args.onPageLoad? and args.onPageLoad
    @$scope.nextLoading = args.onPageLoad? and args.onPageLoad
    @$scope.lastError = false
    @$scope.nextError = false

    if @$scope.draftValid and not @$scope.draftLocked
      lastPromise = @api.Pick.getLast({ draft_id: draft_id, amount: 5 }, lastSuccess, lastErrorHandler)
      nextPromise = @api.Pick.getNext({ draft_id: draft_id, amount: 5 }, nextSuccess, nextErrorHandler)

  _loadCompletedData: (draft_id) =>
    roundSuccess = (data) =>
      @$scope.roundPicks = data
      @$scope.roundLoading = false

    errorHandler = (data) =>
      @messageService.showError "Unable to load picks"
      @$scope.roundError = true
      @$scope.roundLoading = false

    if @$scope.draft != undefined and @$scope.draftValid and not @$scope.draftLocked
      @$scope.roundError = false
      @$scope.roundLoading = true
      roundPromise = @api.Pick.getSelectedByRound({ draft_id: draft_id, round: @$scope.selectedDraftRound, sort_ascending: true }, roundSuccess, errorHandler)



