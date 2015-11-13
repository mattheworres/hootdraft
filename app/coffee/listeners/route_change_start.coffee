angular.module("app").run ($rootScope, $location, authenticationService, messageService) ->
  $rootScope.$on '$routeChangeStart', (event, next, current) ->
    if(!next?)
      $location.path '/error'

    if next.$$route.adminOnly and (not authenticationService.isAuthenticated() or not authenticationService.isAdmin())
      $location.path '/'
      messageService.showError 'Insufficient user privileges to access that page, sorry friend.'