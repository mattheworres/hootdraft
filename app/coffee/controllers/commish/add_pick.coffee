class AddPickController extends BaseController
  @register 'AddPickController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  '$q',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService',
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
        #If there were no matches (99% of the time) or the user confirmed they were OK with it
        if response.matchExists? == false or response.pickIsNotDuplicate? == true
          @_savePick()
        else
          @_wipeOutPick()
          @addInProgress = false
          @messageService.showInfo "Pick was reset - go ahead and enter another player."

      duplicateCheckFailure = (response) =>
        @addInProgress = false
        @messageService.showError "Unable to enter pick - error while checking for duplicates."

      duplicateResult = @_checkForExistingPicks()

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
      @_loadCurrentPick();

    addFailureHandler = (response) =>
      @addInProgress = false

      if response?.status is 400
        addError = response.data?.errors?.join('\n')
      else
        addError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{registerError}", 'Unable to enter pick'

    @api.Pick.add(@$scope.currentPick, addSuccessHandler, addFailureHandler)

  #TODO: Great candidate for draft service, or maybe new Pick service
  _checkForExistingPicks: ->
    deferral = @$q.defer()

    alreadyDraftedSuccess = (data) =>
      if data.possibleMatchExists == false
        deferral.resolve(
          matchExists: data.possibleMatchExists
        )
      else
        duplicatePickModalResult = @_showDuplicatePickModal(@$scope.currentPick, data.matches)

        duplicatePickSuccess = (response) =>
          deferral.resolve(
            matchExists: true,
            pickIsNotDuplicate: response.pickIsNotDuplicate
          )
        duplicatePickError = =>
          deferral.reject(
            error: "Unable to confirm if duplicate player is intended"
          )

        duplicatePickModalResult.promise.then duplicatePickSuccess, duplicatePickError

      deferral.resolve(
        matchExists: data.possibleMatchExists
        matches: data.matches
      )

    errorHandler = (response) =>
      @messageService.showError "Unable to search for player already drafted"
      deferral.reject(
        data: response
        status: response.status
      )

    @api.Pick.alreadyDrafted({draft_id: @$routeParams.draft_id, first_name: @$scope.currentPick.first_name, last_name: @$scope.currentPick.last_name}, alreadyDraftedSuccess, errorHandler)

    return deferral
    #write logic to check for existing when submit - "Yes I am sure" resolves promise to true, cancel wipes pick

  _showDuplicatePickModal: (currentPick, matches) ->
    deferral = @$q.defer()
    #Write something that also uses a promise to show all listed duplicate picks
    @modalInstance = @$modal.open
      templateUrl: 'app/templates/modals/duplicate_pick.html',
      controller: 'DuplicatePickModalController',
      controllerAs: 'modalCtrl',
      resolve:
        duplicateMatches: =>
          matches
        currentPick: =>
          currentPick

    @modalInstance.result.then (clickedYes) =>
      @modalInstance.dismiss('cancel')

      deferral.resolve(
        pickIsNotDuplicate: clickedYes
      )

    return deferral

  closeModal: ->
    @modalInstance?.close?()

  _wipeOutPick: ->
    @$scope.currentPick = @$scope.pristineCurrentPick

  proPlayerSearch: (searchTerm) ->
    @api.Pick.searchProPlayers({league: @$scope.draft.draft_sport, searchTerm: searchTerm}
    ).$promise.then (data) =>
      return @limitToFilter(data.players, 10)
    .catch =>
      @messageService.closeToasts()
      @messageService.showError "Unable to search pro players"

  proPlayerLabel: (player) ->
    if player == undefined
      return

    "#{player.first_name} #{player.last_name} (#{player.position} - #{player.team})"

  selectPlayer: (item, model, label) ->
    @$scope.currentPick = item

    #Perform an eager API call to ensure autocomplete player is not a duplicate
    duplicateCheckSuccess = (response) =>
      #If there were matches and the user wanted to wipe the pick, then do that. Otherwise, do nothing
      if response.matchExists? == true and response.pickIsNotDuplicate? == false
        @_wipeOutPick()
        @messageService.showInfo "Pick was reset - go ahead and enter another player."
      else
        @$scope.playerSearch = ''

    duplicateCheckFailure = (response) =>
      @addInProgress = false
      @messageService.showError "Unable to select pick - error while checking for duplicates."
      @_wipeOutPick()

    duplicateResult = @_checkForExistingPicks()

    duplicateResult.promise.then duplicateCheckSuccess, duplicateCheckFailure




