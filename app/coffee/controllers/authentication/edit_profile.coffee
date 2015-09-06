class EditProfileController extends BaseController
  @register 'EditProfileController'
  @inject '$rootScope', '$scope', '$sessionStorage', '$location', 'authenticationService', 'api', 'workingModalService', 'messageService', 'subscriptionKeys'

  initialize: =>
    @$scope.showPassword = false

    @_loadUserProfileData()

  _loadUserProfileData: ->
    loadSuccess = (data) =>
      @userProfile = data

    errorHandler = (response) ->
      @messageService.showError "Unable to load user profile"

    @api.Authentication.getProfile({}, loadSuccess, errorHandler)

  passwordInputType: =>
    if @$scope.showPassword
      'text'
    else
      'password'

  submitClicked: =>
    if @form.$valid
      @update()

  profileFormIsInvalid: =>
    return @updateInProgress or not @form.$valid

  update: =>
    @workingModalService.openModal()

    updateModel =
      _email: @userProfile.email
      _name: @userProfile.name
      _password: @userProfile.password
      _newPassword: @userProfile.newPassword
      _newConfirmedPassword: @userProfile.newConfirmedPassword

    @updateInProgress = true

    @messageService.closeToasts()

    updateSuccessHandler = (response) =>
      @updateInProgress = false
      @workingModalService.closeModal()
      @form.$setPristine()

      if response.invalidateLogin == true
        @authenticationService.uncacheSession()
        if response.sendEmail == true
          @messageService.showInfo "Your email was updated. In order to login again you must verify your email address. An email has been sent to that account to activate it. You will be logged out for now. See you soon!"
          @$location.path '/home'
        else
          @messageService.showInfo "Since you changed your password, we had to log you out and log you back in again. But no biggie - just enter your new password now to login again."
          @$location.path '/login'
      else 
        if @$sessionStorage.user_name != @userProfile.name
          @authenticationService.cacheName(@userProfile.name)
        @messageService.showInfo "User profile information updated!"
        @sendToPreviousPath()

    updateFailureHandler = (response) =>
      @updateInProgress = false
      @workingModalService.closeModal()
      
      if response.status is 400 and response.data.errors?.length > 0
        updateError = response.data.errors?.join('\n')
      else
        updateError = "Whoops! We hit a snag - looks like it's on our end (#{response.data.status})"

      @messageService.showError "#{updateError}", 'Unable to update your user profile'

    @api.Authentication.setProfile(updateModel, updateSuccessHandler, updateFailureHandler)