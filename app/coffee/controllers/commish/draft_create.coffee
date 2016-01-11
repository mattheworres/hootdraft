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
    @draftLoading = true
    @draftError = false

    draftInitializeSuccess = (data) =>
      @draft = data
      @draftLoading = false

    draftInitializeErrorHandler = () =>
      @draftLoading = false
      @draftError = true
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

      @form.$setPristine()

      @messageService.showSuccess "#{response.draft.draft_name} created!"
      @$location.path "/draft/#{response.draft.draft_id}"

    createFailureHandler = (response) =>
      @createInProgress = false
      @workingModalService.closeModal()

      if response?.status is 400
        registerError = response.data?.errors?.join('\n')
      else
        registerError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{registerError}", 'Unable to create draft'

    @api.Draft.save(createModel, createSuccessHandler, createFailureHandler)



