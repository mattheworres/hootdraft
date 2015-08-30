angular.module("app").run ($rootScope, $sessionStorage, $routeParams, $location, authenticationService, messageService, subscriptionKeys) ->
  $rootScope.$on subscriptionKeys.routeChangeSuccess, (event, current, previous) ->
    if authenticationService.isAuthenticationExpired()
      messageService.showInfo "Your login has expired. You must log back in if you wish to continue."

    if $location.$$path?
      $sessionStorage.$default(
        previousRoutes: []
      )

      $sessionStorage.previousRoutes.push $location.$$path