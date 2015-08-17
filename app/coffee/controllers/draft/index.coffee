class DraftIndexController extends BaseController
  @register 'DraftIndexController'
  @inject '$scope',
  '$rootScope',
  'subscriptionKeys',
  'api',
  'messageService',
  'workingModalService',
  'workingModalService',
  'api'

  initialize: ->
    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and args.draft.setting_up == true
        @_loadSettingUpData(args.draft.draft_id, args)
      else if args.draft? and args.draft.in_progress == true
        @_loadInProgressData(args.draft.draft_id, args)
      else if args.draft? and args.draft.complete == true
        @_loadCompletedData(args.draft.draft_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  _loadSettingUpData: (draft_id, args) =>
    managersSuccess = (data) =>
      @$scope.managersLoading = false
      @$scope.managers = data

    managersError = (response) =>
      @$scope.managersLoading = false
      @$scope.managersError = true
      @messageService.showError "Unable to load managers"

    @$scope.managersLoading = args.onPageLoad? and args.onPageLoad
    @$scope.managersError = false

    managersPromise = @api.Manager.getManagers({ draft_id: draft_id }, managersSuccess, managersError)
    #Round times?

  _loadInProgressData: (draft_id, args) =>
    lastSuccess = (data) =>
      @$scope.lastLoading = false
      @$scope.lastFivePicks = data

    nextSuccess = (data) =>
      @$scope.nextLoading = false
      @$scope.nextFivePicks = data

    lastErrorHandler = (data) =>
      @$scope.lastLoading = false
      @$scope.lastError = true
      console.log "Last error"

    nextErrorHandler = (data) =>
      @$scope.nextLoading = false
      @$scope.nextError = true
      console.log "Next error"
      console.log data

    @$scope.lastLoading = args.onPageLoad? and args.onPageLoad
    @$scope.nextLoading = args.onPageLoad? and args.onPageLoad
    @$scope.lastError = false
    @$scope.nextError = false

    lastPromise = @api.Pick.getLast({ draft_id: draft_id, amount: 5 }, lastSuccess, lastErrorHandler)
    nextPromise = @api.Pick.getNext({ draft_id: draft_id, amount: 5 }, nextSuccess, nextErrorHandler)

  _loadCompletedData: (draft_id, args) =>
    lastSuccess = (data) =>
      @$scope.lastTenPicks = data

    errorHandler = (data) =>
      @messageService.showError "Unable to load picks"

    lastPromise = @api.Pick.getLast({ draft_id: draft_id, amount: 10 }, lastSuccess, errorHandler)