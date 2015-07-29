class HomeController extends BaseController
  @register 'HomeController'
  @inject '$scope',
  'messageService',
  'workingModalService'

  initialize: ->
    @$scope.greeting = 'Welcome to '
    @$scope.appName = "PHP Draft"


    #@workingModalService.openModal(5000)
    #@messageService.showInfo "Yay we have a message!"