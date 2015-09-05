class NavController extends BaseController
  @register 'NavController'
  @inject '$scope', 'messageService', 'confirmActionService'

  initialize: ->
    @draftNavHidden = true

  changeDraftNav: ->
    @draftNavHidden = !@draftNavHidden

  showDeleteDraftModal: ->
    title = "Delete the draft?"
    message = "Are you sure you want to delete the draft? This action cannot be undone."
    iconClass = "fa-exclamation-triangle"
    confirmButtonText = "Yes, Delete the draft"
    deleteDraft = ->
      console.log "Yay, we are deleting the draft."

    @confirmActionService.showConfirmationModal(message, deleteDraft, title, iconClass, confirmButtonText)

  _isDraftEditable: ->
    if @$scope.draft? and @$scope.draft.commish_editable?
      return @$scope.draft.commish_editable
    else
      return false