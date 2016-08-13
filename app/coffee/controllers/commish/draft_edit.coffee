class DraftEditController extends BaseController
  @register 'DraftEditController'
  @inject '$scope',
  '$loading',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService',
  'depthChartPositionService'

  initialize: ->
    @draftEdit = 
      using_depth_charts: false
      depthChartPositions: []
    @depthChartPositionIndex = -1
    @draftLoading = true
    @draftLoaded = false
    @sportChangeListenerRegistered = false
    @draftError = false

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and args.draft.setting_up == true
        #Ensure we only get draft data once, assumption is that there's only 1 person editing it at a given time
        if not @draftLoaded
          @deregister()
          @_loadCommishDraft(args.draft.draft_id)
      else if args.draft? and (args.draft.in_progress == true || args.draft.complete == true)
        @messageService.showWarning "Unable to edit draft: draft has already started or has completed"
        @deregister()
        @sendToPreviousPath()
        @draftError = true

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  # The change of the initial data causes issues, so we only listen for these once the draft has been loaded.
  _bindDraftSpecificListeners: ->
    @$scope.$watch =>
      @draftEdit.depthChartPositions
    , =>
      @hasNonstandardPositions = @depthChartPositionService.calculateRoundsFromPositions(@draftEdit)
      @depthChartsUnique = @depthChartPositionService.getDepthChartPositionValidity(@draftEdit)
    , true

    @$scope.$watch =>
      @draftEdit.using_depth_charts
    , =>
      @hasNonstandardPositions = @depthChartPositionService.calculateRoundsFromPositions(@draftEdit)
      @depthChartsUnique = @depthChartPositionService.getDepthChartPositionValidity(@draftEdit)

      if @draftEdit.using_depth_charts and @sportChangeListenerRegistered and @draftEdit.depthChartPositions.length == 0
        @sportChanged()

    @$scope.$watch 'draftEditCtrl.draftEdit.draft_sport', =>
      @sportChanged()

    @$scope.$watch 'draftEditCtrl.depthChartPositionIndex', =>
      @deleteDepthChartPosition()

  _loadCommishDraft: (draft_id) ->
    @draftLoaded = true

    draftInitializeSuccess = (data) =>
      angular.merge(@draftEdit, data)
      @draftLoading = false
      @_bindDraftSpecificListeners()

    draftInitializeErrorHandler = () =>
      @draftLoading = false
      @draftError = true
      @messageService.showError "Unable to load draft"

    @api.Draft.commishGet({ draft_id: draft_id }, draftInitializeSuccess, draftInitializeErrorHandler)

  sportChanged: ->
    positionsSuccess = (data) =>
      positionResetCallback = =>
        @$loading.finish('load_data')

      @depthChartPositionService.createPositionsBySport(@draftEdit, data.positions, positionResetCallback)

    positionsError = =>
      @$loading.finish('load_data')
      @messageService.showError "Unable to load positions for the given draft sport"

    if @draftEdit?.draft_sport?.length == 0
      return

    #Angular triggers a change when the listenered is registered regardless, so we must ignore the first one ourselves:
    if not @sportChangeListenerRegistered
      @sportChangeListenerRegistered = true
      return

    @$loading.start('load_data')
    @api.DepthChartPosition.getPositions {draft_sport: @draftEdit.draft_sport}, positionsSuccess, positionsError

  editClicked: =>
    if not @editFormIsInvalid()
      @_edit()

  addDepthChartPosition: ->
    @depthChartPositionService.addDepthChartPosition(@draftEdit)

  deleteDepthChartPosition: ->
    if @editInProgress or @depthChartPositionIndex is -1
      return

    @depthChartPositionService.deleteDepthChartPosition(@draftEdit, @depthChartPositionIndex)
    @hasNonstandardPositions = @depthChartPositionService.calculateRoundsFromPositions(@draftEdit)
    @depthChartsUnique = @depthChartPositionService.getDepthChartPositionValidity(@draftEdit)

  editFormIsInvalid: =>
    if @editInProgress or not @form.$valid
      return true
    
    if @draftEdit.using_depth_charts
      return not @depthChartsUnique
    else
      return false

  _edit: =>
    @workingModalService.openModal()

    editModel =
      draft_id: @draftEdit.draft_id
      name: @draftEdit.draft_name
      sport: @draftEdit.draft_sport
      style: @draftEdit.draft_style
      rounds: @draftEdit.draft_rounds
      password: @draftEdit.draft_password
      using_depth_charts: @draftEdit.using_depth_charts
      depthChartPositions: @draftEdit.depthChartPositions

    @editInProgress = true

    @messageService.closeToasts()

    editSuccessHandler = (response) =>
      @editInProgress = false
      @workingModalService.closeModal()

      @form.$setPristine()

      @messageService.showSuccess "#{response.draft.draft_name} edited!"
      @$location.path "/draft/#{@draftEdit.draft_id}"

    editFailureHandler = (response) =>
      @editInProgress = false
      @workingModalService.closeModal()

      if response?.status is 400
        registerError = response.data?.errors?.join('\n')
      else
        registerError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{registerError}", 'Unable to edit draft'

    @api.Draft.update(editModel, editSuccessHandler, editFailureHandler)



