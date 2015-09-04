class AddManagersController extends BaseController
  @register 'AddManagersController'
  @inject '$modalInstance', 'messageService', 'subscriptionKeys',
  '$rootScope',
  'draft_id'

  initialize: =>
    @editableManagers = []

    @addEmptyManager()

  addEmptyManager: =>
    @editableManagers.push(
      manager_name: ''
    )

  removeManager: (index) =>
    #TODO: Does this actually remove the selected object from the array...?
    @editableManagers.splice(index, 1)

  addManagers: =>
    validManagers = []
    for manager in @editableManagers
      if manager.manager_name.trim().length > 0
        validManagers.push(manager)

    addManagerSuccess = (data) =>
      @messageService.showSuccess "Managers added"
      #Need to tell index controller to reload the managers since we added them. Can also call this when deleting managers
      @$rootScope.$broadcast @subscriptionKeys.updateCommishManagers, { draft_id: @draft_id }
      @$modalInstance.dismiss 'closed'

    addManagerError = (response) =>
      @messageService.showError "Unable to add managers"

    if validManagers.length > 0
      @api.Manager.addMultiple({draft_id: @draft_id, managers: validManagers}, addManagerSuccess, addManagerError)
    else
      @cancel()

  cancel: ->
    @$modalInstance.dismiss 'closed'