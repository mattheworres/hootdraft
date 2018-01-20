class @AngularComponent
  @register: (name, type) ->
    name ?= @name || @toString().match(/function\s*(.*?)\(/)?[1]
    angular.module('app')[type]? name, @

  @inject: (args...) ->
    args.push @$inject... if @$inject
    @$inject = args

  constructor: (args...) ->
    #add all of the injected parameters into 'this'
    for key, index in @constructor.$inject
      @[key] = args[index]

    #Expose members of the base class directly onto the child class unless it starts
    #  with an underscore, then it is private
    for key, fn of @constructor.prototype
      continue unless typeof fn is 'function'
      continue if key in ['constructor', 'initialize'] or key[0] is '_'
      #Note: function.bind is not supported in IE < 9
      @[key] = fn.bind?(@)

  #inject global dependencies
  @inject()
