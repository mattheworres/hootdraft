class ByManagerController extends BaseController
  @register 'ByManagerController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  '$timeout',
  '$loading',
  'subscriptionKeys',
  'messageService',
  'api'

  initialize: =>
    @currentDraftCounter = 0

    managersSuccess = (data) =>
      @$scope.managerError = false
      @$scope.managers = data
      [first, ..., last] = data
      @$scope.selectedManager = first
      @_loadManagerPicks(@$routeParams.draft_id, first.manager_id)

    managersError = (response) =>
      @$scope.managerError = true
      @messageService.showError "Unable to grab managers"

    @api.Manager.getManagers({ draft_id: @$routeParams.draft_id }, managersSuccess, managersError)

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      @draftCounterChanged = if args.onPageLoad? and args.onPageLoad then true else @currentDraftCounter != args.draft.draft_counter
      @currentDraftCounter = if args.draft? then args.draft.draft_counter else 0

      if args.draft? and args.draft.setting_up == true
        @$scope.pageError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()
      else if args.draft? and args.draft.in_progress == true
        if @$scope.selectedManager != undefined and @draftCounterChanged
          @_loadManagerPicks(args.draft.draft_id, @$scope.selectedManager.manager_id, args)

        if @draftCounterChanged
          @_loadInProgressPicks(args.draft.draft_id, args)
      else if args.draft? and @$scope.selectedManagerId != undefined and args.draft.complete == true
        @_loadManagerPicks(args.draft.draft_id, @$scope.selectedManager.manager_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    @$scope.$watch ( =>
      @$scope.selectedManager
    ), =>
      if @$scope.selectedManager != undefined
        args =
          onPageLoad: true
        @_loadManagerPicks(@$routeParams.draft_id, @$scope.selectedManager.manager_id, args)

  _loadManagerPicks: (draft_id, selectedManagerId, args) =>
    picksSuccess = (data) =>
      @$scope.managerPicks = data
      #To offset some weirdness on slower connections, wait 1.75 seconds to show the picks.
      @$timeout (=> @$scope.picksLoading = false), 1750

    picksErrorHandler = (data) =>
      @messageService.showError "Unable to load picks"
      @$scope.picksError = true

    @$scope.picksLoading = args? and args.onPageLoad? and args.onPageLoad
    @$scope.picksError = false
    managerPickPromise = @api.Pick.getAllByManager({ draft_id: draft_id, manager_id: selectedManagerId, sort_ascending: true }, picksSuccess, picksErrorHandler)

  _loadInProgressPicks: (draft_id, args) =>
    nextSuccess = (data) =>
      @$scope.nextFivePicks = data
      @$scope.nextLoading = false
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


