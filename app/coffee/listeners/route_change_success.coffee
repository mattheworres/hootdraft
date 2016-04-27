angular.module("app").run ($rootScope, $sessionStorage, $routeParams, $location, authenticationService, messageService, subscriptionKeys) ->
  $rootScope.$on subscriptionKeys.routeChangeSuccess, (event, current, previous) ->
    if authenticationService.isAuthenticationExpired()
      messageService.showInfo "Your login has expired. You must log back in if you wish to continue."
      if $location.$$path? and $location.$$path != '/login'
        $location.path '/login'

    if $location.$$path?
      #Issue with values being reset here... Find a better way to default previousRoutes?
      ###$sessionStorage.$default(
        previousRoutes: []
      )###
      if $sessionStorage.previousRoutes is undefined
        $sessionStorage.previousRoutes = []

      if $sessionStorage.previousRoutes[$sessionStorage.previousRoutes.length - 1] != $location.$$path
        $sessionStorage.previousRoutes.push $location.$$path

    #In the event we're on an XS screen size, tell the menus to auto collapse (see nav.coffee)
    $rootScope.$broadcast subscriptionKeys.collapseMenus