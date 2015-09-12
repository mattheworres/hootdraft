class AddPickController extends BaseController
  @register 'AddPickController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  '$q',
  '$location',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService',
  'authenticationService',
  'pickService',
  'limitToFilter'

  initialize: ->
    @_loadCurrentPick()
    @$scope.manualEntry = false
    @addInProgress = false

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and (args.draft.setting_up == true || args.draft.complete == true)
        if args.draft.setting_up
          @messageService.showWarning "Unable to add picks for draft: draft has not been started yet."
        else if args.draft.complete
          @messageService.showWarning "Unable to add picks for draft: draft is already completed"

        @deregister()
        @sendToPreviousPath()
        @draftError = true

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    #If manual entry, we need to make sure to properly update "selected" on the current pick so the display acts accordingly
    @$scope.$watch ( =>
      @$scope.currentPick
    ), =>
      if @$scope.manualEntry
        #If we have a first AND last name, go ahead and show this as a pick. May not have position coloring, but thats OK.
        hasFirst = @$scope.currentPick.first_name? and @$scope.currentPick.first_name.length > 0
        hasLast = @$scope.currentPick.last_name? and @$scope.currentPick.last_name.length > 0
        hasTeam = @$scope.currentPick.team? and @$scope.currentPick.team.length > 0
        hasPosition = @$scope.currentPick.position? and @$scope.currentPick.position.length > 0

        @$scope.currentPick.selected = hasFirst or hasLast or hasTeam or hasPosition
    , true

  _loadCurrentPick: ->
    @$scope.currentLoading = true

    currentPickSuccess = (data) =>
      @$scope.currentLoading = false
      @$scope.currentPick = data.pick
      @$scope.pristineCurrentPick = data.pick
      @$scope.teams = data.teams
      @$scope.positions = data.positions
      @$scope.next_5_picks = data.next_5_picks
      @$scope.last_5_picks = data.last_5_picks
      @$scope.is_last_pick = data.next_5_picks.length == 0

    errorHandler = (response) =>
      @$scope.currentLoading = false
      @$scope.currentError = true
      @messageService.showError "Unable to get current pick"

    @$scope.currentError = false
    @api.Pick.getCurrent({draft_id: @$routeParams.draft_id}, currentPickSuccess, errorHandler)

  addClicked: =>
    if not @addFormIsInvalid()
      @_add()

  addFormIsInvalid: =>
    return @addInProgress or not @form.$valid

  _add: =>
    @addInProgress = true

    #If the user has chosen to perform manual entry, we just automatically run the check again.
    if @$scope.manualEntry
      duplicateCheckSuccess = (response) =>
        if response.pickIsNotDuplicate == false
          @wipePick()
          @addInProgress = false
          @messageService.showInfo "Pick was reset - go ahead and enter another player."
        else
          @_savePick()

      duplicateCheckFailure = (response) =>
        @addInProgress = false
        @messageService.showError "Unable to enter pick - error while checking for duplicates."

      duplicateResult = @pickService.checkForExistingPicks(@$routeParams.draft_id, @$scope.currentPick)

      duplicateResult.promise.then duplicateCheckSuccess, duplicateCheckFailure
    else
      #just make the pick
      @_savePick()

  _savePick: ->
    #TODO: Figure out how to handle end of draft. Maybe in load current pick?
    @messageService.closeToasts()

    addSuccessHandler = (response) =>
      @addInProgress = false

      @messageService.showSuccess "#{@$scope.currentPick.first_name} #{@$scope.currentPick.last_name} drafted"
      @$scope.manualEntry = false
      
      if not @$scope.is_last_pick
        @_loadCurrentPick()
      else
        #Draft has been completed - ensure commish user *thinks* something big happened, even though this is all instant
        setTimeout =>
          @$location.path "/draft/#{@$routeParams.draft_id}"

          @messageService.showSuccess "Congrats! Your draft has been completed.", "That's a Wrap!"
        , 1500

    addFailureHandler = (response) =>
      @addInProgress = false

      if response?.status is 400
        addError = response.data?.errors?.join('\n')
      else if response?.status is 401
        @messageService.showError "Unauthorized: please log in."
        @authenticationService.uncacheSession()
        @$location.path '/login'
      else
        addError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{addError}", 'Unable to enter pick'

    @api.Pick.add(@$scope.currentPick, addSuccessHandler, addFailureHandler)

  wipePick: ->
    @$scope.manualEntry = false
    @$scope.currentPick = @$scope.pristineCurrentPick

  proPlayerSearch: (searchTerm) ->
    @api.Pick.searchProPlayers({league: @$scope.draft.draft_sport, searchTerm: searchTerm}
    ).$promise.then (data) =>
      return @limitToFilter(data.players, 10)
    .catch =>
      @messageService.closeToasts()
      @messageService.showError "Unable to search pro players"

  selectPlayer: (item, model, label) ->
    item.selected = true
    #Want to keep data about pick (round, pick #) as well as add player name, position, team, so merge not assignment:
    @$scope.currentPick = angular.merge({}, @$scope.currentPick, item)
    delete @$scope.playerSearch
    @$scope.playerSearch = ''

    #Perform an eager API call to ensure autocomplete player is not a duplicate
    duplicateCheckSuccess = (response) =>
      #If there were matches and the user wanted to wipe the pick, then do that. Otherwise, do nothing
      if response.pickIsNotDuplicate == false
        @wipePick()
        @messageService.showInfo "Pick was reset - go ahead and enter another player."
      else
        #@$scope.playerSearch = ''

    duplicateCheckFailure = (response) =>
      @addInProgress = false
      @messageService.showError "Unable to select pick - error while checking for duplicates."
      @wipePick()

    duplicateResult = @pickService.checkForExistingPicks(@$routeParams.draft_id, @$scope.currentPick)

    duplicateResult.promise.then duplicateCheckSuccess, duplicateCheckFailure




