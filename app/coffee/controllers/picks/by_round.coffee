class ByRoundController extends BaseController
  @register 'ByRoundController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  '$timeout',
  '$loading',
  'subscriptionKeys',
  'messageService',
  'api'

  initialize: =>
    @$scope.selectedDraftRound = 1
    @currentDraftCounter = 0

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      @draftCounterChanged = if args.onPageLoad? and args.onPageLoad then true else @currentDraftCounter != args.draft.draft_counter
      @currentDraftCounter = if args.draft? then args.draft.draft_counter else 0

      if args.draft? and args.draft.setting_up == true
        @$scope.pageError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()
      else if args.draft? and args.draft.in_progress == true
        @$scope.pagerItemTally = args.draft.draft_rounds * 10

        if args.onPageLoad? and args.onPageLoad
          @$scope.selectedDraftRound = args.draft.draft_current_round

        if @draftCounterChanged
          @_loadRoundData(args.draft.draft_id, args)
          @_loadInProgressData(args.draft.draft_id, args)
      else if args.draft? and args.draft.complete == true
        @$scope.pagerItemTally = args.draft.draft_rounds * 10
        @_loadRoundData(args.draft.draft_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    @$scope.$watch ( =>
      @$scope.selectedDraftRound
    ), =>
      args = 
        onPageLoad: true

      @_loadRoundData(@$routeParams.draft_id, args)

  _loadRoundData: (draft_id, args) =>
    roundSuccess = (data) =>
      @$scope.roundPicks = data
      #To offset some weirdness on slower connections, wait 1.75 seconds to show the picks.
      @$timeout (=> @$scope.roundPicksLoading = false), 1750

    errorHandler = (data) =>
      @$scope.picksError = true
      @$scope.roundPicksLoading = false
      @messageService.showError "Unable to load picks"

    if @$scope.draftValid and not @$scope.draftLocked
      @$scope.picksError = false
      @$scope.roundPicksLoading = args? and args.onPageLoad? and args.onPageLoad
      roundPromise = @api.Pick.getAllByRound({ draft_id: draft_id, round: @$scope.selectedDraftRound, sort_ascending: true }, roundSuccess, errorHandler)

  _loadInProgressData: (draft_id, args) =>
    nextSuccess = (data) =>
      @$scope.nextLoading = false
      @$scope.nextFivePicks = data
      @$loading.finish('load_next_picks')

    nextErrorHandler = (data) =>
      @$scope.nextLoading = false
      @$scope.nextError = true
      @$loading.finish('load_next_picks')

    @$scope.nextLoading = args? and args.onPageLoad? and args.onPageLoad
    @$scope.nextError = false

    if not args.onPageLoad? or not args.onPageLoad
      @$loading.start('load_next_picks')

    nextPromise = @api.Pick.getNext({ draft_id: draft_id, amount: 5 }, nextSuccess, nextErrorHandler)


