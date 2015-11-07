class BoardController extends BaseController
  @register 'BoardController'
  @inject '$scope',
  '$routeParams',
  'subscriptionKeys',
  'api',
  'messageService'

  initialize: ->
    @initialBoardLoaded = false
    @$scope.boardLoading = true
    @$scope.timerEnabled = false
    @$scope.timerUp = false
    @$scope.timerRunning = false
    @calculatedBoardWidth = "100%"

    @hideFooter()

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
      @_loadTimeRemaining(draft_id)

  _loadUpdatedData: (draft_id) ->
    updatedSuccess = (data) =>
      counterChanged = @$scope.currentDraftCounter != data.draft_counter
      currentPickChanged = @$scope.currentPick

      @$scope.currentDraftCounter = data.draft_counter

      if not counterChanged
        return

      #Rather than hitting these separately, we've been handed them already, so update them.
      #@_loadCurrentAndNextPicks(draft_id)
      @$scope.currentPick = data.current_pick
      @$scope.previousPick = data.previous_pick
      @$scope.hasPreviousPick = @$scope.previousPick? and @$scope.previousPick != undefined

      @_updateBoardPick(data.current_pick)
      @_updateBoardPick(data.previous_pick)

      if not data.updated_picks? or data.updated_picks.length == 0
        return

      for updatedPick in data.updated_picks
        if updatedPick.player_pick >= @$scope.currentPick.player_pick
          @_resetTimer()
        @_updateBoardPick(updatedPick)

    errorHandler = (data) =>
      @messageService.showError "Unable to get up to date draft picks"
      @$scope.boardError = true

    @$scope.boardError = false

    if @$scope.draftValid and not @$scope.draftLocked
      @api.Pick.getUpdated({ draft_id: draft_id, pick_counter: @$scope.currentDraftCounter }, updatedSuccess, errorHandler)
      @_loadTimeRemaining(draft_id)

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
      @$scope.hasPreviousPick = @$scope.previousPick? and @$scope.previousPick != undefined

    errorHandler = (data) =>
      @messageService.showError "Unable to get next and/or current picks"
      @$scope.boardError = true

    @$scope.boardError = false

    if @$scope.draftValid and not @$scope.draftLocked
      @api.Pick.getNext({ draft_id: draft_id, amount: 1 }, currentSuccess, errorHandler)
      @api.Pick.getLast({ draft_id: draft_id, amount: 1 }, lastSuccess, errorHandler)

  _resetTimer: ->
    @timerClockStopHandler()
    @_loadTimeRemaining(@$routeParams.draft_id)

  timerClockStopHandler: ->
    @$scope.timerUp = true
    @$scope.timerRunning = false
    #TODO: Wait 1s, then play sounds here
    #TODO For sound, ensure we're kicked off by clock and not by pick being made

  _loadTimeRemaining: (draft_id) ->
    timersSuccess = (data) =>
      @$scope.timerLoading = false
      @$scope.timerEnabled = data.timer_enabled

      if @$scope.timerEnabled
        seconds = parseInt(data.seconds_remaining, 10)
        @$scope.timerUp = seconds == 0
        if not @$scope.timerRunning and seconds > 0
          @$scope.timerRunning = true
          @$scope.setTime(seconds)
          @$scope.start()

    errorHandler = (data) =>
      @$scope.timerLoading = false
      @$scope.timerError = true
      @messageService.showError "Unable to load remaining pick time"

    if @$scope.draftValid and not @$scope.draftLocked and @$scope.draft.in_progress == true
      @$scope.timerError = false
      @$scope.timerLoading = true
      @api.Draft.getTimeRemaining({ draft_id: draft_id }, timersSuccess, errorHandler)
    else
      @deregister()




