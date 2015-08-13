class @BaseController extends AngularController
  #default dependencies in case @inject is never called from the child
  @$inject: ["$routeParams", "$scope", "$rootScope", "$location", "$sessionStorage",
  "$routeParams", "authenticationService", "messageService",
  "draftService", "subscriptionKeys", "DTOptionsBuilder", "api"]
  @inject: (args...) ->
      args.push '$routeParams'
      args.push '$scope'
      args.push '$rootScope'
      args.push '$location'
      args.push '$sessionStorage'
      args.push '$routeParams'
      args.push 'authenticationService'
      args.push 'messageService'
      args.push 'draftService'
      args.push 'subscriptionKeys'
      args.push 'DTOptionsBuilder'
      args.push 'api'
      super args...

  constructor: ->
    super(arguments...)

    @initialize?()

  isAuthenticated: =>
    @$sessionStorage.authenticated

  authenticatedName: =>
    @$sessionStorage.user_name

  logOut: =>
    @authenticationService.logout()
    @messageService.showInfo("Logged Out")
    @$location.path '/home'

  sendToPreviousPath: ->
    if @$sessionStorage.previousRoute? and not @_pathIsWhitelisted(@$sessionStorage.previousRoute)
      @$location.path @$sessionStorage.previousRoute
    else
      @$location.path '/home'

  defaultDatatablesOptions: ->
    @DTOptionsBuilder
        .withPaginationType('simple')
        .newOptions()
        .withDisplayLength(25)
        .withBootstrap()
        .withBootstrapOptions({
            ColVis: {
                classes: {
                    masterButton: 'btn btn-primary'
                }
            }
          })
        .withColVis()

  _pathIsWhitelisted: (path) ->
    whitelisted_paths = [
      '/login'
      '/verify'
      '/resetPassword'
      '/forgotPassword'
      '/register'
    ]

    whitelisted_paths.some (whitelisted_path) -> ~path.indexOf whitelisted_path