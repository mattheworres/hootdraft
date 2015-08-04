class AuthenticationService extends AngularService
  @register 'authenticationService'
  @inject 'api', 'ENV', '$q', '$sessionStorage'

  cacheSession: (userData) ->
    @$sessionStorage.authenticated = true
    @$sessionStorage.auth_token = userData.token
    @$sessionStorage.user_name = userData.name

  uncacheSession: ->
    @$sessionStorage.authenticated = false
    delete @$sessionStorage.auth_token
    delete @$sessionStorage.user_name

  cacheRoles: (roles) ->
    @$sessionStorage.roles = roles

  isAuthenticated: ->
    @$sessionStorage.authenticated

  login: (model) ->
    result = @$q.defer()

    @uncacheSession()

    successHandler = (data, status, headers, config) =>
      @cacheSession(data)
      result.resolve(
        data: data
        status: status
      )

    errorHandler = (data, status, headers, config) =>
      result.reject(
        data: data
        status: status
      )

    @api.Authentication.login(model, successHandler, errorHandler)
        

    return result

  logout: ->
    @uncacheSession()

  isLoggedIn: ->
    @sessionService.get('authenticated')
