class DepthChartController extends BaseController
  @register 'DepthChartController'
  @inject '$scope',
  '$routeParams',
  '$loading',
  '$sessionStorage',
  'lodash',
  'subscriptionKeys',
  'api',
  'messageService'

  initialize: ->
    @depthChartLoading = true
    @managerChosen = false
    @moveInProgress = false

    @depthChartPositions = []

    @_loadManagers()
    @hideFooter()

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and args.draft.setting_up == true
        @$scope.pageError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()
      else if args.draft? and (args.draft.in_progress == true || args.draft.complete == true)
        @draft = args.draft

        if args.onPageLoad? and args.onPageLoad
          @$scope.currentDraftCounter = args.draft.draft_counter

        #Save extra updated queries by only querying when the counter changes
        @counterChanged = @$scope.currentDraftCounter != args.draft.draft_counter

        if @managerChosen and @counterChanged and not @moveInProgress
          @_loadUpdatedData()
          @$scope.currentDraftCounter = args.draft.draft_counter

        if args.draft.complete == true
          @deregister()

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    @storedManager = @$sessionStorage.depthChartManager

    if @storedManager != undefined and parseInt(@storedManager.draft_id, 10) == parseInt(@$routeParams.draft_id, 10)
      @selectedManager = @storedManager
    else
      delete @$sessionStorage.depthChartManager

    @$scope.$watch 'depthChartCtrl.selectedManager', =>
      if @selectedManager is undefined
        return

      @managerChosen = parseInt(@selectedManager.manager_id, 10) > 0

      if @managerChosen
        @$sessionStorage.depthChartManager = @selectedManager
        @_loadUpdatedData()
    , true

  _loadManagers: ->
    managerSuccess = (data) =>
      @managers = data
      containsValidManager = @selectedManager != undefined

      if @selectedManager != undefined
        containsValidManager = @lodash.findIndex(@managers, 'manager_id': @selectedManager.manager_id) != -1

      if not @managerChosen or not containsValidManager
        @selectedManager = data[0]
        @$sessionStorage.depthChartManager = @selectedManager

      @depthChartLoading = false

    managersError = (data) =>
      @messageService.showError "Unable to load managers"
      @depthChartLoading = false

    @api.Manager.getManagers {draft_id: @$routeParams.draft_id}, managerSuccess, managersError

  enableDragging: ->
    if @draft == undefined
      return false

    draftInProgress = @draft.in_progress
    draftCompleted = @draft.complete

    if draftInProgress
      return true

    if draftCompleted and @draft?.draft_end_time != null
      now = (new Date().getTime()) / 1000
      #Replace dashes with slashes, fixes a weird date initialization bug only in Firefox:
      draftEnd = (new Date(@draft.draft_end_time.replace(/-/g,'/')).getTime()) / 1000
      millisecondsElapsed = now - draftEnd

      return millisecondsElapsed <= 600

    return false

  enableUnassignedTooltips: (position) ->
    return not @moveInProgress and position.position == 'Unassigned' and @enableDragging()

  _loadUpdatedData: ->
    depthChartSuccess = (data) =>
      @moveInProgress = false
      @$loading.finish('loading_depth_chart')
      @depthChartPositions = data.depthChartPositions

    depthChartError = (response) =>
      @moveInProgress = false
      @$loading.finish('loading_depth_chart')
      @messageService.showError "Unable to load depth chart."

    @moveInProgress = true
    @$loading.start('loading_depth_chart')
    @api.DepthChartPosition.getDepthChart {draft_id: @$routeParams.draft_id, manager_id: @selectedManager.manager_id}, depthChartSuccess, depthChartError

  _updateDepthCharts: ->
    if @depthChartPositions == undefined or @depthChartPositions.length == 0
      return

    for position in @depthChartPositions
      for pick in position.picks
        if pick.depth_chart_position_id != position.depth_chart_position_id
          pick.depth_chart_position_id = position.depth_chart_position_id

          updateSuccess = (updatedPick) =>
            @$loading.finish('loading_depth_chart')
            @moveInProgress = false
            pick.depth_chart_position_id = updatedPick.depth_chart_position_id

          updateError = =>
            @$loading.finish('loading_depth_chart')
            @messageService.showError "Unable to update depth chart."

          if not @moveInProgress
            @moveInProgress = true
            @$loading.start('loading_depth_chart')
            @api.DepthChartPosition.update({draft_id: @$routeParams.draft_id, position_id: position.depth_chart_position_id, pick_id: pick.player_id}, updateSuccess, updateError)

  positionStyle: (position) ->
    widthCoefficient = if position.position == 'Unassigned' then 195 else 275
    calculatedWidth = if position.picks?.length then position.picks.length * widthCoefficient else widthCoefficient
    if position.picks.length == 0 or @moveInProgress
      return '100%'
    return "#{calculatedWidth}px"

  positionDetailClass: (position) ->
    className = 'position-default'

    if position.position is 'Unassigned'
      return className

    className += ' position-half'

    if position.picks.length
      positionPercentage = parseInt((position.picks.length / position.slots) * 100, 10)

      if positionPercentage >= 50 and positionPercentage < 100
        className += ' position-three-quarter'
      else if positionPercentage == 100
        className += ' position-full'
      else if positionPercentage > 100
        className += ' position-over-full'

    #console.log "So we have #{className} for #{position.picks.length} out of #{position.slots} slots (#{position.position})"
    return className

  #Rather than relying on the $index value from ng-repeat, we need to manually find the array index
  #rather than the displayed array index Angular uses in order to remove the pick from the original
  #picks array:
  removePickFromPosition: (positionPicks, pick) ->
    pickIndex = @lodash.findIndex(positionPicks, 'player_id': pick.player_id)

    positionPicks.splice(pickIndex, 1)

  #DND Event Handlers
  dnd_dragstart: (event) ->
    @moveInProgress = true

  dnd_dragend: (eventProperty) ->
    @moveInProgress = false
    @_updateDepthCharts()