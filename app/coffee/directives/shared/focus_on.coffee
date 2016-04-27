angular.module("app").directive "focusOn", ($timeout) ->
  restrict: "A"
  link: (scope, element, attrs) ->
    scope.$on attrs.focusOn, (e) ->
      $timeout (-> element[0].focus()), 150