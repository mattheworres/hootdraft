class DraftCreateController extends BaseController
  @register 'DraftCreateController'
  @inject '$scope',
  '$loading',
  '$timeout',
  'workingModalService',
  'api',
  'messageService',
  'depthChartPositionService'

  initialize: ->
    @draft = 
      using_depth_charts: false
    @draft.depthChartPositions = []
    @depthChartPositionIndex = -1
    @draftLoading = true
    @draftError = false

    draftInitializeSuccess = (data) =>
      angular.merge(@draft, data)
      @draftLoading = false

    draftInitializeErrorHandler = =>
      @draftLoading = false
      @draftError = true
      @messageService.showError "Unable to load draft defaults"

    @api.Draft.getCreate({}, draftInitializeSuccess, draftInitializeErrorHandler)

    @$scope.$watch =>
      @draft.depthChartPositions
    , =>
      @hasNonstandardPositions = @depthChartPositionService.calculateRoundsFromPositions(@draft)
      @depthChartsUnique = @depthChartPositionService.getDepthChartPositionValidity(@draft)
    , true

    @$scope.$watch =>
      @draft.using_depth_charts
    , =>
      @hasNonstandardPositions = @depthChartPositionService.calculateRoundsFromPositions(@draft)
      @depthChartsUnique = @depthChartPositionService.getDepthChartPositionValidity(@draft)

    @$scope.$watch 'draftCreateCtrl.draft.draft_sport', =>
      @sportChanged()

    @$scope.$watch 'draftCreateCtrl.depthChartPositionIndex', =>
      @deleteDepthChartPosition()

  createClicked: ->
    if not @createFormIsInvalid()
      @_create()

  sportChanged: ->
    positionsSuccess = (data) =>
      positionResetCallback = =>
        @$loading.finish('load_data')

      @depthChartPositionService.createPositionsBySport(@draft, data.positions, positionResetCallback)

    positionsError = =>
      @$loading.finish('load_data')
      @messageService.showError "Unable to load positions for the given draft sport"

    if @draft?.draft_sport?.length > 0
      @$loading.start('load_data')
      @api.DepthChartPosition.getPositions {draft_sport: @draft.draft_sport}, positionsSuccess, positionsError

  createFormIsInvalid: ->
    if @createInProgress or not @form.$valid
      return true
    
    draftValidity = false

    if @draft.using_depth_charts
      return not @depthChartsUnique
    else
      draftValidity = true

    return draftValidity

  addDepthChartPosition: ->
    @depthChartPositionService.addDepthChartPosition(@draft)

  deleteDepthChartPosition: ->
    if @createInProgress or @depthChartPositionIndex is -1
      return

    @depthChartPositionService.deleteDepthChartPosition(@draft, @depthChartPositionIndex)
    @hasNonstandardPositions = @depthChartPositionService.calculateRoundsFromPositions(@draft)
    @depthChartsUnique = @depthChartPositionService.getDepthChartPositionValidity(@draft)

  _create: ->
    @workingModalService.openModal()

    createModel =
      name: @form.name.$viewValue
      sport: @draft.draft_sport
      style: @draft.draft_style
      rounds: @form.rounds.$viewValue
      password: @form.password.$viewValue
      using_depth_charts: @draft.using_depth_charts
      depthChartPositions: @draft.depthChartPositions

    @createInProgress = true

    @messageService.closeToasts()

    createSuccessHandler = (response) =>
      @createInProgress = false
      @workingModalService.closeModal()

      @form.$setPristine()

      @messageService.showSuccess "#{response.draft.draft_name} created!"
      @$location.path "/draft/#{response.draft.draft_id}"

    createFailureHandler = (response) =>
      @createInProgress = false
      @workingModalService.closeModal()

      if response?.status is 400
        createError = response.data?.errors?.join('\n')
      else
        createError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{createError}", 'Unable to create draft'

    @api.Draft.save(createModel, createSuccessHandler, createFailureHandler)