class WorkingModalController extends BaseController
  @register 'WorkingModalController'
  @inject '$interval',
  'typicalLoadingTimeMs',
  'loadingBarMax',
  'loadingBarIncrement'

  initialize: =>
    if(@typicalLoadingTimeMs != 0)
      @setupLoadingTimer()

  setupLoadingTimer: ->
    @progressIncrements = Math.round((@loadingBarIncrement / @typicalLoadingTimeMs) * 1000) / 10

    loadingIntervalHandler = =>
      @loadingBarMax += @progressIncrements

      if(@loadingBarMax >= 100)
        @$interval.cancel(@intervalPromise)
        @intervalPromise = undefined

    @intervalPromise = @$interval(loadingIntervalHandler, @loadingBarIncrement)