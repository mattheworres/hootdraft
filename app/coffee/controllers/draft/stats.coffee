class StatsController extends BaseController
  @register 'StatsController'
  @inject '$scope',
  '$routeParams',
  'subscriptionKeys',
  'api',
  'messageService'

  initialize: ->
    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and (args.draft.setting_up == true || args.draft.in_progress == true)
        @$scope.pageError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()
      else if args.draft? and args.draft.complete == true
        @_loadStatsData(args.draft.draft_id, args)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  _loadStatsData: (draft_id, args) =>
    statsSuccess = (data) =>
      @$scope.statsLoading = false
      @$scope.stats = data.draft_statistics
      @$scope.stats_generated = data.draft_statistics != null
      console.log "We got stats, and they are #{data.draft_statistics} so, is our bool being set? #{@$scope.stats_generated}"

    errorHandler = (data) =>
      @$scope.statsLoading = false
      @$scope.statsError = true

    @$scope.statsLoading = args.onPageLoad? and args.onPageLoad
    @$scope.statsError = false

    if @$scope.draftValid and not @$scope.draftLocked
      @api.Draft.getStats({ draft_id: draft_id }, statsSuccess, errorHandler)