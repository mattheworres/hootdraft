class @BaseController extends AngularController
  #default dependencies in case @inject is never called from the child
  @$inject: ["$routeParams", "$scope", "$rootScope", "$location", "$sessionStorage",
  "$routeParams", "$window", "authenticationService", "messageService",
  "draftService", "subscriptionKeys", "DTOptionsBuilder"]
  @inject: (args...) ->
      args.push '$routeParams'
      args.push '$scope'
      args.push '$rootScope'
      args.push '$location'
      args.push '$sessionStorage'
      args.push '$routeParams'
      args.push '$window'
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
    storedPreviousRoute = @$sessionStorage.previousRoutes.splice(-2)[0]
    if @$sessionStorage.previousRoutes.length > 1 and not @_pathIsWhitelisted(storedPreviousRoute)
      @$location.path @$sessionStorage.previousRoutes.splice(-2)[0]
    else
      @$location.path '/home'

  showDraftPasswordModal: ->
    @draftService.showPasswordModal()

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