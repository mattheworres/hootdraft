class AuthenticationService extends AngularService
  @register 'authenticationService'
  @inject 'api', '$q', '$sessionStorage'

  cacheSession: (userData) ->
    @$sessionStorage.authenticated = true
    @$sessionStorage.auth_token = userData.token
    @$sessionStorage.user_name = userData.name
    @$sessionStorage.auth_time = userData.auth_timeout

  cacheName: (userName) ->
    delete @$sessionStorage.user_name
    @$sessionStorage.user_name = userName

  uncacheSession: ->
    @$sessionStorage.authenticated = false
    delete @$sessionStorage.auth_token
    delete @$sessionStorage.user_name
    delete @$sessionStorage.auth_time

  cacheRoles: (roles) ->
    @$sessionStorage.roles = roles

  isAuthenticated: ->
    @$sessionStorage.authenticated && @$sessionStorage.user_name? && @$sessionStorage.auth_token?

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

  isAuthenticationExpired: =>
    if not @isAuthenticated()
      return false

    auth_time = new Date(@$sessionStorage.auth_time)
    now = new Date()

    if now > auth_time
      @uncacheSession()
      return true
    else
      return false

  register: (model) ->
    result = @$q.defer()

    successHandler = (data) =>
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

  verify: (model) ->
    result = @$q.defer()

    successHandler = (data) =>
      result.resolve(
        data: data
        status: data.status
      )

    errorHandler = (data) =>
      result.reject(
        data: data
        status: data.status
      )

    @api.Authentication.verify(model, successHandler, errorHandler)

    return result

  lostPassword: (model) ->
    result = @$q.defer()

    successHandler = (data) =>
      result.resolve(
        data: data
        status: data.status
      )

    errorHandler = (data) =>
      result.reject(
        data: data
        status: data.status
      )

    @api.Authentication.lostPassword(model, successHandler, errorHandler)

    return result

  verifyResetToken: (model) ->
    result = @$q.defer()

    successHandler = (data) =>
      result.resolve(
        data: data
        status: data.status
      )

    errorHandler = (data) =>
      result.reject(
        data: data
        status: data.status
      )

    @api.Authentication.verifyResetToken(model, successHandler, errorHandler)

    return result

  resetPassword: (model) ->
    result = @$q.defer()

    successHandler = (data) =>
      result.resolve(
        data: data
        status: data.status
      )

    errorHandler = (data) =>
      result.reject(
        data: data
        status: data.status
      )

    @api.Authentication.resetPassword(model, successHandler, errorHandler)

    return result

