class ResetPasswordController extends BaseController
  @register 'ResetPasswordController'
  @inject '$q',
  'ENV',
  '$scope',
  '$routeParams',
  'messageService',
  'workingModalService',
  'authenticationService',
  '$sessionStorage'

  initialize: ->
    @$scope.showPassword = false
    @$scope.showResetForm = false

    if @authenticationService.isAuthenticated()
      @messageService.showInfo "Already logged in as #{@$sessionStorage.user_name}.", 'Logged In'
      @sendToPreviousPath()

    @email = @$routeParams.email
    @resetToken = @$routeParams.token

    if not @email? or not @resetToken?
      @messageService.showError "Invalid token or email"
      @$scope.showResetForm = false
      return

    verifyModel =
      _email: @email
      _verificationToken: @resetToken

    verifyResult = @authenticationService.verifyResetToken(verifyModel)

    verificationSuccess = (response) =>
      @$scope.showResetForm = true

    verificationFailure = (response) =>
      @$scope.showResetForm = false

    verifyResult.promise.then verificationSuccess, verificationFailure
    return

  passwordInputType: =>
    if @$scope.showPassword
      'text'
    else
      'password'

  submitClicked: =>
    if @form.$valid
      @resetPassword()

  resetFormIsInvalid: =>
    return @resetInProgress or not @form.$valid

  resetPassword: =>
    @workingModalService.openModal()

    resetModel =
      _email: @email
      _password: @form.password.$viewValue
      _verificationToken: @resetToken
      _confirmPassword: @form.confirmedPassword.$viewValue

    @resetInProgress = true

    resetResult = @authenticationService.resetPassword(resetModel)

    @messageService.closeToasts()

    resetSuccessHandler = (response) =>
      @resetInProgress = false
      @workingModalService.closeModal()

      @form.$setPristine()

      @$location.path '/login'

      @messageService.showInfo "Your password has been set - you may log in now"

    resetFailureHandler = (response) =>
      @resetInProgress = false
      @workingModalService.closeModal()
      
      if response?.data?.status is 400
        resetError = response.data.data?.errors?.join('\n')
      else
        resetError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{resetError}", 'Unable to Reset Password'

    resetResult.promise.then resetSuccessHandler, resetFailureHandler