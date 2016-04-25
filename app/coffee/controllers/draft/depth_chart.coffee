class BoardController extends BaseController
  @register 'BoardController'
  @inject '$scope',
  '$routeParams',
  '$loading',
  'subscriptionKeys',
  'api',
  'messageService'

  initialize: ->
    @loading = true
    @managerChosen = false
    @moveInProgress = false

    @_loadManagers()
    @hideFooter()

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and args.draft.setting_up == true
        @$scope.pageError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()
      else if args.draft? and (args.draft.in_progress == true || args.draft.complete == true)
        if args.onPageLoad? and args.onPageLoad
          @$scope.currentDraftCounter = args.draft.draft_counter

        #Save extra updated queries by only querying when the counter changes
        @counterChanged = @$scope.currentDraftCounter != args.draft.draft_counter

        if @managerChosen and @counterChanged and not @moveInProgress
          @_loadUpdatedData args.draft.draft_id

        if args.draft.complete == true
          @deregister()

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()


    #TODO: Write manager change listener

  _loadManagers: ->
    managerSuccess = (data) =>
      @managers = data.managers
      @loading = false

    managersError = (data) =>
      @messageService.showError "Unable to load managers"
      @loading = false

    @api.Manager.getManagers({draft_id: @$routeParams.draft_id}, managerSuccess, managersError)

  _loadUpdatedData: ->
    #TODO: Write get depth chart info

  #TODO: Write event listeners for Angular Drag Drop