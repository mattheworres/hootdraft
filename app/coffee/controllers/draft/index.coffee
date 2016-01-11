class DraftIndexController extends BaseController
  @register 'DraftIndexController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  '$loading',
  '$timeout',
  'subscriptionKeys',
  'api',
  'messageService'

  initialize: ->
    @$scope.selectedDraftRound = 1
    @currentDraftCounter = 0

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      @draftCounterChanged = if args.onPageLoad? and args.onPageLoad then true else @currentDraftCounter != args.draft.draft_counter
      @currentDraftCounter = if args.draft? then args.draft.draft_counter else 0

      if args.draft? and args.draft.setting_up == true

        @_loadSettingUpData(args.draft.draft_id, args)
      else if args.draft? and args.draft.in_progress == true
        if @draftCounterChanged
          @_loadInProgressData(args.draft.draft_id, args)
      else if args.draft? and args.draft.complete == true
        @$scope.pagerItemTally = @$rootScope.draft.draft_rounds * 10
        @_loadCompletedData(args.draft.draft_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    @$scope.$watch ( =>
      @$scope.selectedDraftRound
    ), =>
      if @$scope.draft != undefined and @$scope.draft.draft_id == @$routeParams.draft_id and @$scope.draft.complete and @$scope.draftValid and not @$scope.draftLocked
        @_loadCompletedData(@$routeParams.draft_id)

  _loadSettingUpData: (draft_id, args) =>
    managersSuccess = (data) =>
      @$scope.managersLoading = false
      @$scope.managers = data

    managersError = (response) =>
      @$scope.managersLoading = false
      @$scope.managersError = true
      @messageService.showError "Unable to load managers"

    @$scope.managersErrorLoading = args.onPageLoad? and args.onPageLoad
    @$scope.commishManagersLoading = args.onPageLoad? and args.onPageLoad
    @$scope.managersError = false

    if @$scope.draftValid and not @$scope.draftLocked
      if args.draft.commish_editable? and args.draft.commish_editable == false
        @api.Manager.getManagers({ draft_id: draft_id }, managersSuccess, managersError)
      
      if args.onPageLoad? and args.onPageLoad and args.draft.commish_editable
        @$timeout (=> @$rootScope.$broadcast @subscriptionKeys.updateCommishManagers, { draft: args.draft }), 250
        

  _loadInProgressData: (draft_id, args) =>
    lastSuccess = (data) =>
      @$loading.finish('load_last_picks')
      @$scope.lastLoading = false
      @$scope.lastFivePicks = data

    nextSuccess = (data) =>
      @$loading.finish('load_next_picks')
      @$scope.nextLoading = false
      @$scope.nextFivePicks = data

    lastErrorHandler = (data) =>
      @$loading.finish('load_last_picks')
      @$scope.lastLoading = false
      @$scope.lastError = true

    nextErrorHandler = (data) =>
      @$loading.finish('load_next_picks')
      @$scope.nextLoading = false
      @$scope.nextError = true

    @$scope.lastLoading = args.onPageLoad? and args.onPageLoad
    @$scope.nextLoading = args.onPageLoad? and args.onPageLoad

    if not args.onPageLoad? or not args.onPageLoad
      @$loading.start('load_last_picks')
      @$loading.start('load_next_picks')

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



