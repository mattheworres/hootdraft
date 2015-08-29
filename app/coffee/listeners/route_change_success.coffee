angular.module("app").run ($rootScope, $sessionStorage, $routeParams, $location, subscriptionKeys) ->
  $rootScope.$on subscriptionKeys.routeChangeSuccess, (event, current, previous) ->
    if $location.$$path?
      $sessionStorage.$default(
        previousRoutes: []
      )

      $sessionStorage.previousRoutes.push $location.$$path