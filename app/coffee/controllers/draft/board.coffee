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
    @$scope.timerEnabled = false
    @$scope.timerUp = false
    @$scope.timerRunning = false
    @calculatedBoardWidth = "100%";

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
          @_loadUpdatedData(args.draft.draft_id, true)
        else
          @_loadUpdatedData(args.draft.draft_id, false)

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
      #@_loadCurrentAndNextPicks(draft_id)
      #@_loadTimeRemaining(draft_id)

  _loadUpdatedData: (draft_id, is_on_load) ->
    updatedSuccess = (data) =>
      draft_counter = parseInt(@$scope.currentDraftCounter, 10)
      counterChanged = is_on_load or not @$scope.currentDraftCounter == data.draft_counter
      currentPickChanged = @$scope.currentPick

      @$scope.currentDraftCounter = data.draft_counter

      if counterChanged
        console.log "So, the current pick is pick ##{data.current_pick.player_pick}, so lets update to such."
        @$scope.currentPick = data.current_pick
        @_updateBoardPick(data.current_pick)

      if data.updated_picks?.length > 0
        for updatedPick in data.updated_picks
          @_updateBoardPick(updatedPick)

          if not @$scope.previousPick?
            @$scope.previousPick = updatedPick
            @$scope.hasPreviousPick = true

          updatedPickNumber = parseInt(updatedPick.player_pick, 10)
          previousPickNumber = parseInt(@$scope.previousPick.player_pick)

          console.log "Is #{updatedPick.player_pick} >= #{@$scope.previousPick.player_pick}? #{updatedPick.player_pick >= @$scope.previousPick.player_pick}"
          if updatedPickNumber >= previousPickNumber
            @$scope.previousPick = updatedPick
            @$scope.hasPreviousPick = true

      if counterChanged
        @$scope.timerEnabled = data.timer_enabled
        if @$scope.timerEnabled
          seconds = parseInt(data.seconds_remaining, 10)
          @$scope.timerUp = seconds == 0
          if not @$scope.timerRunning and seconds > 0
            @$scope.timerRunning = true
            @$scope.setTime(seconds)
            @$scope.start()

    errorHandler = (data) =>
      @messageService.showError "Unable to get up to date draft picks"
      @$scope.boardError = true

    @$scope.boardError = false

    if @$scope.draftValid and not @$scope.draftLocked
      counter = if is_on_load then 0 else @$scope.currentDraftCounter
      console.log "Beacuse #{is_on_load} then #{counter}"
      @api.Pick.getUpdated({ draft_id: draft_id, pick_counter: counter }, updatedSuccess, errorHandler)

  _updateBoardPick: (updatedPick) ->
    if @$scope.pickRounds is undefined
      return

    for roundXPick, index in @$scope.pickRounds[updatedPick.player_round-1]
      if roundXPick.player_id == updatedPick.player_id
        @$scope.pickRounds[updatedPick.player_round-1][index] = updatedPick

  ###_loadCurrentAndNextPicks: (draft_id) ->
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
      @api.Pick.getLast({ draft_id: draft_id, amount: 1 }, lastSuccess, errorHandler)###

  timerClockStopHandler: ->
    @$scope.timerUp = true
    @$scope.timerRunning = false
    #TODO: Wait 1s, then play sounds here
    #TODO For sound, ensure we're kicked off by clock and not by pick being made

  ###_loadTimeRemaining: (draft_id) ->
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
      @deregister()###




