class DraftEditController extends BaseController
  @register 'DraftEditController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService'

  initialize: ->
    @draftLoading = true
    @draftLoaded = false
    @draftError = false

    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) =>
      console.log "We gots a draft and need to laod dep data"
      if args.draft? and args.draft.setting_up == true
        #Ensure we only get draft data once, assumption is that there's only 1 person editing it at a given time
        if not @draftLoaded
          @deregister()
          @_loadCommishDraft(args.draft.draft_id)
      else if args.draft? and (args.draft.in_progress == true || args.draft.complete == true)
        @messageService.showWarning "Unable to edit draft: draft has already started or has completed"
        @deregister()
        @sendToPreviousPath()
        @draftError = true

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()

  _loadCommishDraft: (draft_id) =>
    @draftLoaded = true

    draftInitializeSuccess = (data) =>
      @draftEdit = data
      @draftLoading = false

    draftInitializeErrorHandler = () =>
      @draftLoading = false
      @draftError = true
      @messageService.showError "Unable to load draft"

    @api.Draft.commishGet({ draft_id: draft_id }, draftInitializeSuccess, draftInitializeErrorHandler)

  editClicked: =>
    if not @editFormIsInvalid()
      @_edit()

  editFormIsInvalid: =>
    return @editInProgress or not @form.$valid

  _edit: =>
    @workingModalService.openModal()

    editModel =
      draft_id: @draftEdit.draft_id
      name: @draftEdit.draft_name
      sport: @draftEdit.draft_sport
      style: @draftEdit.draft_style
      rounds: @draftEdit.draft_rounds
      password: @draftEdit.draft_password

    @editInProgress = true

    @messageService.closeToasts()

    editSuccessHandler = (response) =>
      @editInProgress = false
      @workingModalService.closeModal()

      @form.$setPristine()

      @messageService.showSuccess "#{response.draft.draft_name} edited!"
      @$location.path "/draft/#{@draftEdit.draft_id}"

    editFailureHandler = (response) =>
      @editInProgress = false
      @workingModalService.closeModal()

      if response?.status is 400
        registerError = response.data?.errors?.join('\n')
      else
        registerError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{registerError}", 'Unable to edit draft'

    @api.Draft.update(editModel, editSuccessHandler, editFailureHandler)



