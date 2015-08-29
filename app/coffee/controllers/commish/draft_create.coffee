class DraftCreateController extends BaseController
  @register 'DraftCreateController'
  @inject '$scope',
  '$rootScope',
  '$routeParams',
  'subscriptionKeys',
  'workingModalService',
  'api',
  'messageService'

  initialize: ->
    @$scope.selectedDraftRound = 1

    @draftLoading = true

    draftInitializeSuccess = (data) =>
      @draft = data
      @draftLoading = false

    draftInitializeErrorHandler = () =>
      @messageService.showError "Unable to load draft defaults"

    @api.Draft.getCreate({}, draftInitializeSuccess, draftInitializeErrorHandler)

  createClicked: =>
    if not @createFormIsInvalid()
      @_create()

  createFormIsInvalid: =>
    return @registerInProgress or not @form.$valid

  _create: =>
    @workingModalService.openModal()

    createModel =
      name: @form.name.$viewValue
      sport: @sport
      style: @style
      rounds: @form.rounds.$viewValue
      password: @form.password.$viewValue

    @createInProgress = true

    @messageService.closeToasts()

    createSuccessHandler = (response) =>
      @createInProgress = false
      @workingModalService.closeModal()

      console.log "Success brother. Heres what we get: "
      console.log response

      @form.$setPristine()

      @messageService.showSuccess "#{@form.draft_name.$viewValue} created!"
      @$location "/draft/#{response.draft.draft_id}"

    createFailureHandler = (response) =>
      @createInProgress = false
      @workingModalService.closeModal()

      if response?.status is 400
        registerError = response.data?.errors?.join('\n')
      else
        registerError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{registerError}", 'Unable to create draft'

    @api.Draft.save(createModel, createSuccessHandler, createFailureHandler)



