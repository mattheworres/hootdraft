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

