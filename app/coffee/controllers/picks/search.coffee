class SearchController extends BaseController
  @register 'SearchController'
  @inject '$scope',
  '$routeParams',
  '$loading',
  'subscriptionKeys',
  'messageService',
  'api'

  initialize: =>
    @$scope.keywords = ''
    @$scope.team = ''
    @$scope.position = ''
    @$scope.sort = 'DESC'
    @currentDraftCounter = 0

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      @draftCounterChanged = if args.onPageLoad? and args.onPageLoad then true else @currentDraftCounter != args.draft.draft_counter
      @currentDraftCounter = if args.draft? then args.draft.draft_counter else 0

      if args.draft? and args.draft.setting_up == true
        @$scope.pageError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()
      else if args.draft? and (args.draft.in_progress == true || args.draft.complete == true)
        if @draftCounterChanged
          @_loadSearchData(args.draft.draft_id)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    @$scope.$watch ( =>
      @$scope.keywords + @$scope.team + @$scope.position + @$scope.sort
    ), =>
      args = 
        onPageLoad: true

      @_loadSearchData(@$routeParams.draft_id, args)

  hasSearchItems: ->
    @$scope.keywords?.length > 0 || @$scope.team?.length > 0 || @$scope.position?.length > 0

  clearSearchCriteria: ->
    @$scope.keywords = ''
    @$scope.position = ''
    @$scope.team = ''

  toggleSort: ->
    if @$scope.sort == 'DESC'
      @$scope.sort = 'ASC'
    else
      @$scope.sort = 'DESC'

  _loadSearchData: (draft_id, args) =>
    searchSuccess = (data) =>
      @$scope.searchResults = data.player_results
      @$scope.searchLoading = false
      @$loading.finish('load_search_picks')

    errorHandler = (data) =>
      @$scope.searchError = true
      @$scope.searchLoading = false
      @messageService.showError "Unable to search picks"
      @$loading.finish('load_search_picks')

    if @$scope.draftValid and not @$scope.draftLocked and @hasSearchItems()
      @$scope.searchError = false
      @$scope.searchLoading = args? and args.onPageLoad? and args.onPageLoad
      @$loading.start('load_search_picks')
      @api.Pick.search({ draft_id: draft_id, keywords: @$scope.keywords, team: @$scope.team, position: @$scope.position, sort: @$scope.sort }, searchSuccess, errorHandler)
    else if not @hasSearchItems()
      if @$scope.searchResults? and @$scope.searchResults.length > 0
        @$scope.searchResults = []



