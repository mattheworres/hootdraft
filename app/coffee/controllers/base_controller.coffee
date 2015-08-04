class @BaseController extends AngularController
  #default dependencies in case @inject is never called from the child
  @$inject: ["$routeParams", "$scope", "$location", "$sessionStorage", "authenticationService", "messageService"]
  @inject: (args...) ->
      args.push '$routeParams'
      args.push '$scope'
      args.push '$location'
      args.push '$sessionStorage'
      args.push 'authenticationService'
      args.push 'messageService'
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