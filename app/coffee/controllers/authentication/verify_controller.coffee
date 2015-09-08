class VerificationController extends BaseController
  @register 'VerificationController'
  @inject '$q',
  '$scope',
  '$routeParams',
  'messageService',
  'workingModalService',
  'authenticationService',
  '$sessionStorage'

  initialize: ->
    @$scope.showErrorInformation = false

    if @authenticationService.isAuthenticated()
      @messageService.showInfo "Already logged in as #{@$sessionStorage.user_name}.", 'Logged In'
      @sendToPreviousPath()

    email = @$routeParams.email
    token = @$routeParams.token

    if not email? or not token?
      @messageService.showError "Invalid verification data"
      @$scope.showErrorInformation = true
      return

    @workingModalService.openModal()

    successHandler = (response) =>
      setTimeout =>
        @workingModalService.closeModal()

        @$scope.showErrorInformation = false

        @$location.path '/login'

        @messageService.showInfo "Your account has been enabled - you may log in now"
      , 3500

    failureHandler = (response) =>
      @workingModalService.closeModal()

      @$scope.showErrorInformation = true
      
      if response?.data?.status is 400
        verifyError = response.data.data?.errors?.join('\n')
      else
        verifyError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{verifyError}", 'Unable to verify'

    verificationModel = 
      _email: email
      _verificationToken: token

    verificationResult = @authenticationService.verify(verificationModel)

    @messageService.closeToasts()

    verificationResult.promise.then successHandler, failureHandler