class @BaseController extends AngularController
  #default dependencies in case @inject is never called from the child
  @$inject: ["$routeParams", "$scope", "$location"]
  @inject: (args...) ->
      args.push '$routeParams'
      args.push '$scope'
      args.push '$location'
      super args...

  constructor: ->
      super(arguments...)
      @initialize?()