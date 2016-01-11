class RegisterController extends BaseController
  @register 'RegisterController'
  @inject '$q',
  'ENV',
  '$scope',
  'messageService',
  'workingModalService',
  'authenticationService',
  '$sessionStorage',
  'vcRecaptchaService'

  initialize: ->
    @$scope.showPassword = false
    @registerInProgress = false
    @$scope.showRegistrationForm = true

    if @authenticationService.isAuthenticated()
      @messageService.showInfo "Already logged in as #{@$sessionStorage.user_name}.", 'Logged In'
      @sendToPreviousPath()

    @$scope.recaptchaPublicKey = @ENV.recaptchaPublicKey

    @$scope.setRecaptchaResponse = (response) =>
      @form._recaptcha = response

  passwordInputType: =>
    if @$scope.showPassword
      'text'
    else
      'password'

  submitClicked: =>
    if @form.$valid
      @register()

  registerFormIsInvalid: =>
    return @registerInProgress or not @form.$valid or @form._recaptcha?.length == 0

  register: =>
    @workingModalService.openModal()

    registerModel =
      _email: @form.email.$viewValue
      _name: @form.name.$viewValue
      _password: @form.password.$viewValue
      _confirmPassword: @form.confirmedPassword.$viewValue
      _recaptcha: @form._recaptcha

    @registerInProgress = true

    registerResult = @authenticationService.register(registerModel)

    @messageService.closeToasts()

    registerSuccessHandler = (response) =>
      @registerInProgress = false
      @workingModalService.closeModal()

      @$scope.newUserName = @form.name.$viewValue
      @$scope.newUserEmail = @form.email.$viewValue

      @form.$setPristine()
      @$scope.showRegistrationForm = false

      @messageService.showInfo "Verification email sent"

    registerFailureHandler = (response) =>
      @registerInProgress = false
      @workingModalService.closeModal()
      
      if response?.data?.status is 400
        registerError = response.data.data?.errors?.join('\n')
      else
        registerError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{registerError}", 'Unable to register'

    registerResult.promise.then registerSuccessHandler, registerFailureHandler