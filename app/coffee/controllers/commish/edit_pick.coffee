class EditPickController extends BaseController
  @register 'EditPickController'
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
    @$scope.selectedDraftRound = 1
    @$scope.currentPick = {}
    @$scope.pristineCurrentPick = {}
    @showPickSelection = true
    @editInProgress = false
    @$scope.manualEntry = true

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and (args.draft.setting_up == true || args.draft.complete == true)
        if args.draft.setting_up
          @messageService.showWarning "Unable to edit picks for draft: draft has not been started yet."
        else if args.draft.complete
          @messageService.showWarning "Unable to edit picks for draft: draft is already completed"
      else if args.draft? and args.draft.in_progress == true
        @$scope.pagerItemTally = args.draft.draft_rounds * 10

        if args.onPageLoad? and args.onPageLoad
          @$scope.selectedDraftRound = args.draft.draft_current_round

    @$scope.$watch ( =>
      @$scope.selectedDraftRound
    ), =>
      args = 
        onPageLoad: true

      @_loadRoundData(@$routeParams.draft_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  _loadRoundData: (draft_id, args) =>
    roundSuccess = (data) =>
      @$scope.roundPicks = data
      @$scope.roundHasPicks = data.length > 0
      @$scope.roundPicksLoading = false

    errorHandler = (data) =>
      @$scope.picksError = true
      @$scope.roundPicksLoading = false
      @messageService.showError "Unable to load picks"

    if @$scope.draftValid and not @$scope.draftLocked
      @$scope.picksError = false
      @$scope.roundPicksLoading = args? and args.onPageLoad? and args.onPageLoad
      roundPromise = @api.Pick.getSelectedByRound({ draft_id: draft_id, round: @$scope.selectedDraftRound, sort_ascending: true }, roundSuccess, errorHandler)

  selectEditPick: (pick) ->
    @$scope.currentPick = pick
    @$scope.pristineCurrentPick = pick
    @showPickSelection = false

  _savePick: ->
    @messageService.closeToasts()

    editSuccessHandler = (response) =>
      @editInProgress = false

      @messageService.showSuccess "#{@$scope.currentPick.first_name} #{@$scope.currentPick.last_name} updated"
      @sendToPreviousPath()

    editFailureHandler = (response) =>
      @editInProgress = false

      if response?.status is 400
        addError = response.data?.errors?.join('\n')
      else if response?.status is 401
        @messageService.showError "Unauthorized: please log in."
        @authenticationService.uncacheSession()
        @$location.path '/login'
      else
        addError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{addError}", 'Unable to edit pick'

    @api.Pick.update(@$scope.currentPick, editSuccessHandler, editFailureHandler)

  




