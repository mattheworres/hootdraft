class LostPasswordController extends BaseController
  @register 'LostPasswordController'
  @inject '$q',
  'ENV',
  '$scope',
  'messageService',
  'workingModalService',
  'authenticationService',
  '$sessionStorage',
  'vcRecaptchaService'

  initialize: ->
    @$scope.showLostForm = true

    if @authenticationService.isAuthenticated()
      @messageService.showInfo "Already logged in as #{@$sessionStorage.user_name}.", 'Logged In'
      @sendToPreviousPath()

    @$scope.recaptchaPublicKey = @ENV.recaptchaPublicKey

    @$scope.setRecaptchaResponse = (response) =>
      @form._recaptcha = response

  submitClicked: =>
    if @form.$valid
      @lostPassword()

  lostFormIsInvalid: =>
    return @lostInProgress or not @form.$valid or @form._recaptcha?.length == 0

  lostPassword: =>
    @workingModalService.openModal()

    lostModel =
      _email: @form.email.$viewValue
      _recaptcha: @form._recaptcha

    @lostInProgress = true

    lostResult = @authenticationService.lostPassword(lostModel)

    @messageService.closeToasts()

    lostSuccessHandler = (response) =>
      @lostInProgress = false
      @workingModalService.closeModal()

      @$scope.userEmail = @form.email.$viewValue

      @form.$setPristine()
      @$scope.showLostForm = false

      @messageService.showInfo "Reset password email sent"

    lostFailureHandler = (response) =>
      @lostInProgress = false
      @workingModalService.closeModal()
      
      if response?.data?.status is 400
        lostError = response.data.data?.errors?.join('\n')
      else
        lostError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{lostError}", 'Unable to Request New Password'

    lostResult.promise.then lostSuccessHandler, lostFailureHandler