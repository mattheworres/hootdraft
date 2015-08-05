class AuthenticationService extends AngularService
  @register 'authenticationService'
  @inject 'api', '$q', '$sessionStorage'

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

    successHandler = (data) =>
      @cacheSession(data)
      result.resolve(
        data: data
        status: data.status
      )

    errorHandler = (data) =>
      result.reject(
        data: data
        status: data.status
      )

    @api.Authentication.login(model, successHandler, errorHandler)

    return result

  logout: ->
    @uncacheSession()

  isLoggedIn: ->
    @sessionService.get('authenticated')

  register: (model) ->
    result = @$q.defer()

    successHandler = (data) =>
      @cacheSession(data)
      result.resolve(
        data: data
        status: data.status
      )

    errorHandler = (data) =>
      result.reject(
        data: data
        status: data.status
      )

    @api.Authentication.register(model, successHandler, errorHandler)

    return result

