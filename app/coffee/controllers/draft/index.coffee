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
        @_loadInProgressData(args.draft.draft_id)
      else if args.draft? and args.draft.complete == true
        @_loadCompletedData(args.draft.draft_id)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  _loadSettingUpData: (draft_id, args) =>
    managersSuccess = (data) =>
      @$scope.managers = data

    managersError = (response) =>
      @messageService.showError "Unable to load managers"

    managersPromise = @api.Manager.getManagers({ draft_id: draft_id }, managersSuccess, managersError)
    #Round times?

  _loadInProgressData: (draft_id) =>
    lastSuccess = (data) =>
      @$scope.lastFivePicks = data

    nextSuccess = (data) =>
      @$scope.nextFivePicks = data

    errorHandler = (data) =>
      @messageService.showError "Unable to load picks"

    lastPromise = @api.Pick.getLast({ draft_id: draft_id, amount: 5 }, lastSuccess, errorHandler)
    nextPromise = @api.Pick.getNext({ draft_id: draft_id, amount: 5 }, nextSuccess, errorHandler)

  _loadCompletedData: (draft_id) =>
    lastSuccess = (data) =>
      @$scope.lastTenPicks = data

    errorHandler = (data) =>
      @messageService.showError "Unable to load picks"

    lastPromise = @api.Pick.getLast({ draft_id: draft_id, amount: 10 }, lastSuccess, errorHandler)