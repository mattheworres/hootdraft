class PickTimersController extends BaseController
  @register 'PickTimersController'
  @inject '$scope',
  '$routeParams',
  '$q',
  '$loading',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService'

  initialize: ->
    @$scope.pickTimerDataLoading = true

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      if args.draft? and (args.draft.setting_up == true || args.draft.in_progress == true)
        if args.draft.draft_id == parseInt(@$routeParams.draft_id, 10)
          @_loadPickTimerData(args.draft.draft_id, args)
          @deregister()
      else if args.draft? and args.draft.complete == true
        @$scope.pickTimerDataError = true
        @sendToPreviousPath()
        @messageService.showWarning "Draft is still setting up"
        @deregister()

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

    @$scope.$watch ( =>
      @$scope.pickTimers
    ), =>
      @_calculateTotalDraftTime()
    , true

    @$scope.$watch ( =>
      @$scope.useStaticTime
    ), =>
      @_calculateTotalDraftTime()

  _loadPickTimerData: (draft_id, args) =>
    pickTimerDataSuccess = (data) =>
      totalManagerSeconds = 0
      #Convert total seconds to split minutes/seconds for each round time
      for timer in data
        timer.minutes = Math.floor(timer.round_time_seconds / 60)
        timer.seconds = timer.round_time_seconds - (timer.minutes * 60)
        totalManagerSeconds += timer.round_time_seconds

      @$scope.pickTimers = data
      @$scope.useStaticTime = @$scope.pickTimers? and @$scope.pickTimers[0].is_static_time == "1"
      @$scope.roundTimesEnabled = totalManagerSeconds > 0

    managersSuccess = (data) =>
      @$scope.numberOfManagers = data.length

    errorHandler = (data) =>
      @$scope.pickTimerDataLoading = false
      @$scope.pickTimerDataError = true

    @$scope.pickTimerDataError = false

    if @$scope.draftValid and not @$scope.draftLocked
      @$loading.start('load_timers')

      timersPromise = @api.Draft.getTimers({ draft_id: draft_id }, pickTimerDataSuccess, errorHandler)
      managersPromise = @api.Manager.getManagers({ draft_id: draft_id }, managersSuccess, errorHandler)

      @$q.all([timersPromise, managersPromise]).then =>
        @$scope.pickTimerDataLoading = false

        @$loading.finish('load_timers')
        if @$scope.roundTimesEnabled
          @_calculateTotalDraftTime()

    else
      @$scope.pickTimerDataLoading = false
      @$scope.pickTimerDataError = true

  submitClicked: =>
    if @form.$valid
      @saveTimers()

  timersFormIsInvalid: =>
    return @saveInProgress or not @form.$valid

  saveTimers: ->
    @workingModalService.openModal()

    timersToSave = []

    if @$scope.roundTimesEnabled
      if @$scope.useStaticTime
        #Only grab first timer
        firstTimer = @$scope.pickTimers[0]
        firstTimer.is_static_time = true
        firstTimer.round_time_seconds = (firstTimer.minutes * 60) + firstTimer.seconds
        firstTimer.draft_id = @$routeParams.draft_id
        timersToSave.push firstTimer
      else
        #Grab 'em all
        for timer in @$scope.pickTimers
          timer.is_static_time = false
          timer.round_time_seconds = (timer.minutes * 60) + timer.seconds
          timer.draft_id = @$routeParams.draft_id
          timersToSave.push timer

    saveModel =
      draft_id: @$routeParams.draft_id
      isRoundTimesEnabled: @$scope.roundTimesEnabled
      roundTimes: timersToSave

    @saveInProgress = true

    @messageService.closeToasts()

    saveSuccessHandler = (response) =>
      @saveInProgress = false
      @workingModalService.closeModal()

      @form.$setPristine()

      @messageService.showInfo "Round timers saved!"
      @sendToPreviousPath()

    saveFailureHandler = (response) =>
      @saveInProgress = false
      @workingModalService.closeModal()
      
      if response.status is 400 and response.data.errors?.length > 0
        saveError = response.data.errors?.join('\n')
      else
        saveError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{saveError}", 'Unable to save timers'

    @api.Draft.setTimers(saveModel, saveSuccessHandler, saveFailureHandler)

  setSuggestedRoundTimes: ->
    if @timersFormIsInvalid() || @$scope.numberOfManagers == 0
      return

    @$scope.roundTimesEnabled = true
    @$scope.useStaticTime = false

    for roundTime in @$scope.pickTimers
      switch
        when roundTime.draft_round <= 1
          roundTime.minutes = 4
          roundTime.seconds = 0
        when roundTime.draft_round <= 3
          roundTime.minutes = 3
          roundTime.seconds = 30
        when roundTime.draft_round <= 5
          roundTime.minutes = 3
          roundTime.seconds = 15
        when roundTime.draft_round <= 7
          roundTime.minutes = 3
          roundTime.seconds = 0
        when roundTime.draft_round <= 10
          roundTime.minutes = 2
          roundTime.seconds = 30
        when roundTime.draft_round <= 13
          roundTime.minutes = 1
          roundTime.seconds = 45
        when roundTime.draft_round <= 15
          roundTime.minutes = 1
          roundTime.seconds = 15
        when roundTime.draft_round <= 20
          roundTime.minutes = 1
          roundTime.seconds = 0
        when roundTime.draft_round <= 30
          roundTime.minutes = 0
          roundTime.seconds = 45
        else
          roundTime.minutes = 0
          roundTime.seconds = 5

    @_calculateTotalDraftTime()

  _calculateTotalDraftTime: ->
    if not @$scope.pickTimers? || @$scope.numberOfManagers == undefined
      @$scope.totalDraftingTime = 0
      return

    secondsPerManager = 0

    if @$scope.useStaticTime
      roundTime = @$scope.pickTimers[0]
      secondsPerRound = (roundTime.minutes * 60) + roundTime.seconds
      secondsPerManager = secondsPerRound * @$scope.pickTimers.length
    for roundTime in @$scope.pickTimers
      totalSeconds = (roundTime.minutes * 60) + roundTime.seconds
      secondsPerManager += totalSeconds

    if @$scope.numberOfManagers == 0
      @$scope.totalDraftingTime = 0
    else
      @$scope.totalDraftingTime = secondsPerManager * @$scope.numberOfManagers

