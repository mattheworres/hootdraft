class LoginController extends BaseController
  @register 'LoginController'
  @inject '$q',
  '$scope',
  'api',
  'messageService',
  'workingModalService',
  'authenticationService',
  '$sessionStorage'

  initialize: ->
    @$scope.showPassword = false

    if @authenticationService.isAuthenticated()
      @messageService.showInfo "Already logged in as #{@$sessionStorage.user_name}.", 'Logged In'
      @sendToPreviousPath()

  passwordInputType: =>
    if @$scope.showPassword
      'text'
    else
      'password'

  submitClicked: =>
    if @form.$valid
      @login()

  login: =>
    @workingModalService.openModal()

    loginModel =
      _email: @form.email.$viewValue
      _password: @form.password.$viewValue

    loginResult = @authenticationService.login(loginModel)

    @messageService.closeToasts()
    
    @loginInProgress = true

    loginSuccessHandler = (response) =>
      @loginInProgress = false
      @workingModalService.closeModal()

      @sendToPreviousPath()

      @messageService.showSuccess "Welcome back, #{@$sessionStorage.user_name}!", 'Logged In'

    loginFailureHandler = (response) =>
      @loginInProgress = false
      @workingModalService.closeModal()
      
      if response.status is 400
        loginError = response.data.data?.errors?.join('\n')
      else
        loginError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{loginError}", 'Unable to Login'

    loginResult.promise.then loginSuccessHandler, loginFailureHandler