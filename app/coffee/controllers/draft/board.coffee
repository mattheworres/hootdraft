class BoardController extends BaseController
  @register 'BoardController'
  @inject '$scope',
  '$routeParams',
  'subscriptionKeys',
  'api',
  'messageService'

  initialize: ->
    @initialBoardLoaded = false
    @$scope.boardLoading = false
    @calculatedBoardWidth = "100%";

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and args.draft.setting_up == true
        @$scope.pageError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()
      else if args.draft? and (args.draft.in_progress == true || args.draft.complete == true)
        #Rather than rely on draft query to update counter, we need to use it to gather updated pick data.
        #Set it once on load, then update it with the _loadUpdatedData method from then on
        if args.onPageLoad? and args.onPageLoad
          @$scope.currentDraftCounter = args.draft.draft_counter

        if not @initialBoardLoaded
          @_loadInitialBoard args.draft.draft_id
        else
          @_loadUpdatedData args.draft.draft_id

        if args.draft.complete == true
          @deregister()

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  _calculateBoardWidth: (numberOfManagers) ->
    #See width in board.less for the magic numbers below
    #managers * pick width + round number width + left and right borders
    numberOfPixels = (numberOfManagers * 175) + 50 + 4

    @calculatedBoardWidth = "#{numberOfPixels}px"

  _loadInitialBoard: (draft_id) ->
    @initialBoardLoaded = true
    @$scope.boardLoading = true

    initialSuccess = (data) =>
      @$scope.pickRounds = data.allPicks
      @$scope.boardLoading = false
      numberOfManagers = @$scope.pickRounds[0].length
      @_calculateBoardWidth(numberOfManagers)

    errorHandler = (data) =>
      @$scope.boardLoading = false
      @$scope.boardError = true
      @messageService.showError "Unable to load draft board"

    if @$scope.draftValid and not @$scope.draftLocked
      @$scope.boardError = false
      @api.Pick.getAll({ draft_id: draft_id }, initialSuccess, errorHandler)
      @_loadCurrentAndNextPicks(draft_id)

  _loadUpdatedData: (draft_id) ->
    updatedSuccess = (data) =>
      counterChanged = @$scope.currentDraftCounter != data.draft_counter

      @$scope.currentDraftCounter = data.draft_counter

      if data.picks.length == 0
        return

      if counterChanged
        @_loadCurrentAndNextPicks(draft_id)

      for updatedPick in data.picks
        @_updateBoardPick(updatedPick)

    errorHandler = (data) =>
      @messageService.showError "Unable to get up to date draft picks"
      @$scope.boardError = true

    @$scope.boardError = false

    if @$scope.draftValid and not @$scope.draftLocked
      @api.Pick.getUpdated({ draft_id: draft_id, pick_counter: @$scope.currentDraftCounter }, updatedSuccess, errorHandler)

  _updateBoardPick: (updatedPick) ->
    if @$scope.pickRounds is undefined
      return

    for roundXPick, index in @$scope.pickRounds[updatedPick.player_round-1]
      if roundXPick.player_id == updatedPick.player_id
        @$scope.pickRounds[updatedPick.player_round-1][index] = updatedPick

  _loadCurrentAndNextPicks: (draft_id) ->
    currentSuccess = (data) =>
      @$scope.currentPick = data[0]
      if @initialBoardLoaded
        @_updateBoardPick(data[0])

    lastSuccess = (data) =>
      @$scope.previousPick = data[0]

    errorHandler = (data) =>
      @messageService.showError "Unable to get next and/or current picks"
      @$scope.boardError = true

    @$scope.boardError = false

    if @$scope.draftValid and not @$scope.draftLocked
      @api.Pick.getNext({ draft_id: draft_id, amount: 1 }, currentSuccess, errorHandler)
      @api.Pick.getLast({ draft_id: draft_id, amount: 1 }, lastSuccess, errorHandler)




