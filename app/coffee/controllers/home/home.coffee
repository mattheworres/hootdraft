class HomeController extends BaseController
  @register 'HomeController'
  @inject '$scope',
  'messageService'

  initialize: ->
    @$scope.greeting = 'Welcome to '
    @$scope.appName = "PHP Draft"
    
    @messageService.showInfo "Yay we have a message!"