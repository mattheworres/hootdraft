class CommishManagersController extends AngularController
  @register 'CommishManagersController'
  @$inject: ["$scope", "$rootScope", "$q", "$routeParams", "messageService", "subscriptionKeys", "draftService", "api"]
  @inject: (args...) ->
      args.push '$scope'
      args.push '$rootScope'
      args.push '$q'
      args.push '$routeParams'
      args.push 'messageService'
      args.push 'subscriptionKeys'
      args.push 'draftService'
      args.push 'api'
      super args...

  constructor: ->
    super(arguments...)

    @initialize?()

  initialize: ->
    @editableManagers = []

    @deregister_commish_managers = @$rootScope.$on @subscriptionKeys.updateCommishManagers, (event, args) =>
      @_reloadEditableManagers(args.draft.draft_id, args.draft.commish_editable)

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister_commish_managers()

    
    @_reloadEditableManagers()

  _reloadEditableManagers: (draft_id, draft_commish_editable) =>
    commishManagersSuccess = (data) =>
      @commishManagersLoading = false
      @editableManagers = data
      @_resetManagerEdits()

    managersError = (response) =>
      @managersLoading = false
      @managersError = true
      @messageService.showError "Unable to load managers"

    if @$routeParams.draft_id? and draft_commish_editable
      @api.Manager.commishGet({ draft_id: draft_id }, commishManagersSuccess, managersError)

  reorderManagers: (event, spliceIndex, originalIndex) =>
    manager = @editableManagers[originalIndex]
    @editableManagers.splice originalIndex, 1
    @editableManagers.splice spliceIndex, 0, manager
    
    @_saveManagerOrder()

    return true

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
      @_resetManagerEdits()

    errorHandler = =>
      @messageService.showError "Unable to delete manager"
      @editableManagers.splice index, 0, originalManager
      @_reorderInMemoryManagers()
      @_resetManagerEdits()
    
    @api.Manager.delete({draft_id: @$routeParams.draft_id, manager_id: originalManager.manager_id}, saveSuccess, errorHandler)

  _saveManagerOrder: (managers) ->
    result = @$q.defer()

    reorderSuccess = (data) =>
      @commishManagersLoading = false
      @_reorderInMemoryManagers()
      result.resolve()

    reorderError = (response) =>
      @commishManagersLoading = false
      @messageService.showError "Unable to reorder managers"
      result.reject()

    @commishManagersLoading = true
    manager_ids = []
    draft_order = 1
    for manager in @editableManagers
      manager_ids.push manager.manager_id

    @api.Manager.reorder({ draft_id: @$routeParams.draft_id, ordered_manager_ids: manager_ids }, reorderSuccess, reorderError)

    return result

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

  beforeSwipe: (event) =>
    event.preventDefault()