angular.module("app").run ($rootScope, $sessionStorage) ->
  $rootScope.$on '$routeChangeSuccess', (event, current, previous) ->
    if(previous?)
      $sessionStorage.previousRoute = previous