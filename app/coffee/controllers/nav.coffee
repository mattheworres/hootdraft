class NavController extends BaseController
  @register 'NavController'
  @inject '$scope', '$routeParams', 'messageService', 'confirmActionService', 'api'

  initialize: ->
    @draftNavHidden = true

  changeDraftNav: ->
    @draftNavHidden = !@draftNavHidden

  showDeleteDraftModal: ->
    title = "Delete the draft?"
    message = "Are you sure you want to delete the draft? This action cannot be undone."
    iconClass = "fa-exclamation-triangle"
    confirmButtonText = "Yes, Delete the draft"
    deleteDraft = =>
      deleteSuccess = =>
        @messageService.showSuccess "Draft deleted"
        @$location.path '/home'

      deleteError = =>
        @messageService.showError "Unable to delete draft"

      @api.Draft.delete({draft_id: @$routeParams.draft_id }, deleteSuccess, deleteError)

    @confirmActionService.showConfirmationModal(message, deleteDraft, title, iconClass, confirmButtonText)

  _isDraftEditable: ->
    if @$scope.draft? and @$scope.draft.commish_editable?
      return @$scope.draft.commish_editable
    else
      return false