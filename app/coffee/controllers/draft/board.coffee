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
          @$scope.currentDraftCounter = args.draft.draft_current_round

        if not @initialBoardLoaded
          @_loadStatsData args.draft.draft_id, args
        else
          @_loadUpdatedData args.draft.draft_id

        if args.draft.complete == true
          @deregister()

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  _loadInitialBoard: (draft_id) ->
    @initialBoardLoaded = true
    @$scope.boardLoading = true

    initialSuccess = (data) =>
      #First, setup an empty 2D array, one array per round:
      @$scope.pickRounds = []
      for i in [0...@$scope.draft.draft_rounds]
        @$scope.pickRounds[i] = []

      #@$scope.picks = data
      #Then, go thru in order (assumption: server hands us picks in standard/serpentine order already)
      #and add picks to their applicable round array
      for pick in data
        @$scope.pickRounds[pick.player_round-1].push(pick)

      @$scope.boardLoading = false

    errorHandler = (data) =>
      @$scope.boardLoading = false
      @$scope.boardError = true
      @messageService.showError "Unable to load draft board"

    if @$scope.draftValid and not @$scope.draftLocked
      @$scope.boardError = false
      @api.Pick.getAll({ draft_id: draft_id }, initialSuccess, errorHandler)

  _loadUpdatedData: (draft_id) =>
    updatedSuccess = (data) =>
      @$scope.currentDraftCounter = data.draft_counter

      if data.picks.length == 0
        return

      for updatedPick in data.picks
        for roundXPick in @$scope.pickRounds[updatedPick.player_round-1]
          if roundXPick.player_id == updatedPick.player_id
            roundXPick = updatedPick

    errorHandler = (data) =>
      @messageService.showError "Unable to get up to date draft picks"
      @$scope.boardError = true

    @$scope.statsLoading = args.onPageLoad? and args.onPageLoad
    @$scope.boardError = false

    if @$scope.draftValid and not @$scope.draftLocked
      @api.Draft.getStats({ draft_id: draft_id }, statsSuccess, errorHandler)