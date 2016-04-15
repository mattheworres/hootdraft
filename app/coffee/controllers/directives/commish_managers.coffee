class CommishManagersController extends AngularController
  @register 'CommishManagersController'
  @$inject: ["$scope", "$rootScope", "$q", "$routeParams", "messageService", "subscriptionKeys", "draftService", "api", "$loading", "$timeout", "confirmActionService"]
  @inject: (args...) ->
      args.push '$scope'
      args.push '$rootScope'
      args.push '$q'
      args.push '$routeParams'
      args.push 'messageService'
      args.push 'subscriptionKeys'
      args.push 'draftService'
      args.push 'api'
      args.push '$loading'
      args.push '$timeout'
      args.push 'confirmActionService'
      super args...

  constructor: ->
    super(arguments...)

    @initialize?()

  initialize: ->
    @editableManagers = []
    @showCountDown = false
    @countDown = 3

    @deregister_commish_managers = @$rootScope.$on @subscriptionKeys.updateCommishManagers, (event, args) =>
      @_reloadEditableManagers(args.draft.draft_id, args.draft.commish_editable)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister_commish_managers()

    
    @_reloadEditableManagers()

  ## Slip Events
  reorderManagers: (event, spliceIndex, originalIndex) =>
    manager = @editableManagers[originalIndex]
    @editableManagers.splice originalIndex, 1
    @editableManagers.splice spliceIndex, 0, manager
    
    @_saveManagerOrder()

    return true

  beforeSwipe: (event) =>
    event.preventDefault()

  beforeWait: (event) =>
    if(event.target.className.indexOf('fa') == -1)
      event.preventDefault()

  ## Event Handlers
  openAddManagerModal: ->
    @_resetManagerEdits()
    @draftService.showAddManagersModal @$routeParams.draft_id

  deleteManager: (index) ->
    if @isEditActive
      return

    originalManager = @editableManagers[index]
    @editableManagers.splice index, 1

    saveSuccess = (data) =>
      @editableManagers = data.managers
      @_setViewPropertyOnManagers(true)

      @_resetManagerEdits()

    errorHandler = =>
      @messageService.showError "Unable to delete manager"
      @editableManagers.splice index, 0, originalManager
      @_reorderInMemoryManagers()
      @_resetManagerEdits()
    
    @api.Manager.delete({draft_id: @$routeParams.draft_id, manager_id: originalManager.manager_id}, saveSuccess, errorHandler)

  enableManagerEdit: (index) ->
    if @isEditActive
      return

    manager = @editableManagers[index]
    manager.isBeingEdited = true
    @isEditActive = true
    @_editedManagerIndex = index
    @_editedManagerOriginalName = manager.manager_name

  cancelManagerEdit: (index) ->
    manager = @editableManagers[index]
    manager.isBeingEdited = false
    manager.managerSaving = false
    @isEditActive = false
    manager.manager_name = @_editedManagerOriginalName
    @_editedManagerIndex = null

  saveManager: (index) ->
    manager = @editableManagers[index]
    manager.managerSaving = true
    
    updateSuccess = (data) =>
      @isEditActive = false
      manager.isBeingEdited = false
      manager.managerSaving = false
      @messageService.showSuccess "#{manager.manager_name} updated!"

    errorHandler = (response) =>
      manager.managerSaving = false
      @messageService.showError "Unable to update manager"

    @api.Manager.update({draft_id: @$routeParams.draft_id, manager_id: manager.manager_id, name: manager.manager_name}, updateSuccess, errorHandler)

  randomizeDraftOrder: =>
    title = "Randomize the draft order?"
    message = "Want to use Javascript's built in random number generator to randomize your draft's selection order? Great! Make sure everyone's watching to see where they end up!"
    iconClass = "fa-random"
    confirmButtonText = "Yes, Randomize this draft!"

    @confirmActionService.showConfirmationModal(message, @_randomizationProcess, title, iconClass, confirmButtonText)

  ## Private methods
  _randomizationProcess: =>
    @commishManagersLoading = true
    @randomizeInProgress = true
    randomizationMinimumTimeMet = false
    managerSaveSuccess = false

    @_setViewPropertyOnManagers(false)

    @_shuffleDraftOrder()

    saveResult = @_saveManagerOrder()

    #Holding the loading display for a second or two so it looks like it's doing "heavy" lifting...
    @$timeout =>
      randomizationMinimumTimeMet = true

      if managerSaveSuccess is true
        randomizationPresentation()
    , 1750

    randomizationPresentation = =>
      @commishManagersLoading = false
      @countDown = 3
      @showCountDown = true
      stepCount = 1
      totalManagers = @editableManagers.length
      currentManagerIndex = 0
      @intervalMilliseconds = 1100
      @shouldContinue = true

      @timingLoop = =>
        if @shouldContinue == false
          return

        if stepCount < 3
          @countDown--
          stepCount++
          @$timeout @timingLoop, @intervalMilliseconds
          return
        else
          @showCountDown = false

        @editableManagers[currentManagerIndex].shown = true

        #Set the grading interval for the next loop
        @intervalMilliseconds = switch
          when currentManagerIndex < 2 then 2500
          when currentManagerIndex is 2 then 1800
          when currentManagerIndex is 3 or currentManagerIndex is 4 then 600
          when currentManagerIndex >= 5 then 400

        if currentManagerIndex + 1 == totalManagers
          @$timeout =>
            @randomizeInProgress = false
          , 750
          @shouldContinue = false
        else
          currentManagerIndex++

        if @shouldContinue
          @$timeout @timingLoop, @intervalMilliseconds

      @$timeout @timingLoop, @intervalMilliseconds

    managerSaveSuccess = (result) =>
      managerSaveSuccess = true

      if randomizationMinimumTimeMet is true
        randomizationPresentation()

    managerSaveError = (result) =>
      @commishManagersLoading = false
      @managersError = true
      @messageService.showError "Unable to randomize draft order - error while saving managers."

    saveResult.promise.then managerSaveSuccess, managerSaveError

  _reloadEditableManagers: (draft_id, draft_commish_editable) =>
    commishManagersSuccess = (data) =>
      @commishManagersLoading = false
      @editableManagers = data

      #Set visibility flag by default
      @_setViewPropertyOnManagers(true)

      @_resetManagerEdits()

    managersError = (response) =>
      @managersLoading = false
      @managersError = true
      @messageService.showError "Unable to load managers"

    if @$routeParams.draft_id? and draft_commish_editable
      @editableManagers = []
      @api.Manager.commishGet({ draft_id: draft_id }, commishManagersSuccess, managersError)

  _saveManagerOrder: () ->
    result = @$q.defer()

    reorderSuccess = (data) =>
      @$loading.finish('saving_order')
      @_reorderInMemoryManagers()
      result.resolve()

    reorderError = (response) =>
      @$loading.finish('saving_order')
      @messageService.showError "Unable to reorder managers"
      result.reject()

    @$loading.start('saving_order')
    manager_ids = []
    draft_order = 1
    for manager in @editableManagers
      manager_ids.push manager.manager_id

    @api.Manager.reorder({ draft_id: @$routeParams.draft_id, ordered_manager_ids: manager_ids }, reorderSuccess, reorderError)

    return result

  _resetManagerEdits: ->
    @isEditActive = false
    if @_editedManagerOriginalName? and @_editedManagerOriginalName.length > 0 and @_editedManagerIndex? and @_editedManagerIndex != null
      @cancelManagerEdit @_editedManagerIndex

  _reorderInMemoryManagers: ->
    draft_order = 1
    for manager in @editableManagers
      manager.draft_order = draft_order
      draft_order++
    return

  #Fisher–Yates shuffle algorithm - from http://stackoverflow.com/a/20791049/324527
  _shuffleDraftOrder: ->
    if @editableManagers.length == 0
      return

    m = @editableManagers.length

    #While there remain elements to shuffle
    while (m)
      #Pick a remaining element…
      i = Math.floor(Math.random() * m--)

      #And swap it with the current element.
      manager = @editableManagers[m]
      @editableManagers.splice m, 1
      @editableManagers.splice i, 0, manager

  _setViewPropertyOnManagers: (viewSetting) ->
    for manager in @editableManagers
      manager.shown = viewSetting