class @BaseController extends AngularController
  #default dependencies in case @inject is never called from the child
  @$inject: ["$routeParams", "$scope", "$location", "$sessionStorage"]
  @inject: (args...) ->
      args.push '$routeParams'
      args.push '$scope'
      args.push '$location'
      super args...

  constructor: ->
    super(arguments...)
    @initialize?()

  isAuthenticated: =>
    @$sessionStorage.authenticated

  authenticatedName: =>
    @$sessionStorage.username