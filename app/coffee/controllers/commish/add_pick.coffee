class AddPickController extends BaseController
  @register 'AddPickController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  '$q',
  '$location',
  '$loading',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService',
  'authenticationService'

  initialize: ->
    @$scope.currentPick = {}
    @_loadCurrentPick()
    @addInProgress = false
    @$scope.manualEntry = false

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
    @$loading.start('load_current')
    @$scope.currentLoading = true

    currentPickSuccess = (data) =>
      @$loading.finish('load_current')
      @$scope.currentLoading = false
      @$scope.currentPick = data.pick
      @$scope.pristineCurrentPick = data.pick
      @$scope.teams = data.teams
      @$scope.positions = data.positions
      @$scope.next_5_picks = data.next_5_picks
      @$scope.last_5_picks = data.last_5_picks
      @$scope.is_last_pick = data.next_5_picks.length == 1
      @$scope.$broadcast @subscriptionKeys.focusPlayerAutocomplete

    errorHandler = (response) =>
      @$loading.finish('load_current')
      @$scope.currentLoading = false
      @$scope.currentError = true
      @messageService.showError "Unable to get current pick"

    @$scope.currentError = false
    @api.Pick.getCurrent({draft_id: @$routeParams.draft_id}, currentPickSuccess, errorHandler)

  _savePick: ->
    @messageService.closeToasts()
    @workingModalService.openModal()

    addSuccessHandler = (response) =>
      @addInProgress = false
      @workingModalService.closeModal()

      @messageService.showSuccess "#{@$scope.currentPick.first_name} #{@$scope.currentPick.last_name} drafted"
      @$scope.manualEntry = false
      
      if not @$scope.is_last_pick
        @_loadCurrentPick()
      else
        @deregister()
        @workingModalService.closeModal()
        @workingModalService.openModal()
        #Draft has been completed - ensure commish user *thinks* something big happened, even though this is all instant
        setTimeout =>
          @$location.path "/draft/#{@$routeParams.draft_id}"

          @workingModalService.closeModal()

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

  




