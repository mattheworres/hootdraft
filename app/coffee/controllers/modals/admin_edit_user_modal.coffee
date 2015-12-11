class AdminEditUserController extends BaseController
  @register 'AdminEditUserController'
  @inject '$modalInstance', 'messageService', 'api', 'user', 'userUpdateCallback'

  initialize: =>
    #Just making the roles static for now. Later, we should inject them and do this dynamically
    @roleDisplays = [
      {value: 'ROLE_COMMISH', label: 'Commissioner'},
      {value: 'ROLE_ADMIN', label: 'Site Administrator'}
    ]

    @user.enabled = @user.enabled == '1'
    @userIsCommish = @user.roles.indexOf('ROLE_COMMISH') != -1
    @userIsAdmin = @user.roles.indexOf('ROLE_ADMIN') != -1
    #@selectedRoles = @user.roles.split ','

  userFormIsInvalid: =>
    return @actionInProgress or not @form.$valid

  saveUser: ->
    if @userFormIsInvalid()
      return

    #Here, convert from the array of objects to a comma-separated list
    hasBoth = @userIsCommish and @userIsAdmin
    adminString = ''
    commaString = ''
    commishString = ''

    if @userIsAdmin
      adminString = 'ROLE_ADMIN'

    if @userIsCommish
      commishString = 'ROLE_COMMISH'

    if hasBoth
      commaString = ','

    @user.roles = "#{adminString}#{commaString}#{commishString}"

    saveSuccess = =>
      @userUpdateCallback(@user)
      @$modalInstance.dismiss 'closed'

    saveError = =>
      @messageService.showError "Unable to update user"

    console.log "Attempting to update user:"
    console.log @user
    @api.User.update(@user, saveSuccess, saveError)

  cancel: ->
    @$modalInstance.dismiss 'closed'