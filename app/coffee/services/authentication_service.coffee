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

  login: (model) ->
    result = @$q.defer()

    @uncacheSession()

    #authData = "grant_type=password&username=#{model.login}&password=#{model.password}"
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
    ###@$http(
        method: 'POST'
        url: "#{@ENV.webEndpoint}token"
        data: authData
        headers:
            'Content-Type': 'application/x-www-form-url-encoded'
    )
    .success (data, status, headers, config) =>
        
    .error (data, status, headers, config) ->###
        

    return result

  logout: ->
    @uncacheSession()

  isLoggedIn: ->
    @sessionService.get('authenticated')
