class NavController extends BaseController
  @register 'NavController'
  @inject '$rootScope',
    '$scope',
    '$routeParams',
    '$location',
    'messageService',
    'subscriptionKeys',
    'confirmActionService',
    'api',
    'errorService'

  initialize: ->
    @draftNavHidden = true

    #When we catch wind to collapse the menus (on xs screen sizes only), set those variables
    @$scope.$on @subscriptionKeys.collapseMenus, (event, args) =>
      @$scope.navCollapsed = true
      @$scope.draftNavCollapsed = true

  changeDraftNav: ->
    @draftNavHidden = !@draftNavHidden

  showDeleteDraftModal: ->
    title = "Delete the draft?"
    message = "Are you sure you want to delete the draft? This action cannot be undone."
    iconClass = "fa-trash"
    confirmButtonText = "Yes, Delete the draft"
    deleteDraft = =>
      deleteSuccess = =>
        @messageService.showSuccess "Draft deleted"
        @$location.path '/home'

      deleteError = =>
        @messageService.showError "Unable to delete draft"

      @api.Draft.delete({draft_id: @$routeParams.draft_id }, deleteSuccess, deleteError)

    @confirmActionService.showConfirmationModal(message, deleteDraft, title, iconClass, confirmButtonText)

  showStartDraftModal: ->
    title = "Start draft?"
    message = "Cool, ready to start your draft? Just make sure all managers have been added and your league's details are correct - you can't change them once your draft has been started. Are you ready to get this show on the road?"
    iconClass = "fa-play"
    confirmButtonText = "Yep! Let's do this!"
    startDraft = =>
      startSuccess = =>
        @messageService.showSuccess "Draft started"
        @$rootScope.draft.setting_up = false
        @$rootScope.draft.in_progress = true
        @$rootScope.$broadcast @subscriptionKeys.loadDraftDependentData, { draft: @$rootScope.draft, onPageLoad: true }

      startError = (response) =>
        startErrors = ''
        if response.data?.errors?
          startErrors = @errorService.joinErrorsForToastDisplay(response.data.errors)

        @messageService.showError "Unable to start draft    #{startErrors}"

      @api.Draft.updateStatus({draft_id: @$routeParams.draft_id, status: 'in_progress'}, startSuccess, startError)

    @confirmActionService.showConfirmationModal(message, startDraft, title, iconClass, confirmButtonText)

  showResetDraftModal: ->
    title = "Reset draft?"
    message = "Uh oh, something wrong? No problem, we can reset your draft. Fair warning though - any and all picks or trades you've made will be deleted forever. Are you sure?"
    iconClass = "fa-exclamation-triangle"
    confirmButtonText = "Yes, reset my draft"
    resetDraft = =>
      resetSuccess = =>
        @messageService.showSuccess "Draft reset"
        @$rootScope.draft.setting_up = true
        @$rootScope.draft.in_progress = false
        @$rootScope.$broadcast @subscriptionKeys.loadDraftDependentData, { draft: @$rootScope.draft, onPageLoad: true }
        @$location.path "/draft/#{@$routeParams.draft_id}"

      resetError = (response) =>
        restartErrors = ''
        if response.data?.errors?
          restartErrors = @errorService.joinErrorsForToastDisplay(response.data.errors)

        @messageService.showError "Unable to reset draft    #{restartErrors}"

      @api.Draft.updateStatus({draft_id: @$routeParams.draft_id, status: 'undrafted'}, resetSuccess, resetError)

    @confirmActionService.showConfirmationModal(message, resetDraft, title, iconClass, confirmButtonText)

  _isDraftEditable: ->
    if @$scope.draft? and @$scope.draft.commish_editable?
      return @$scope.draft.commish_editable
    else
      return false