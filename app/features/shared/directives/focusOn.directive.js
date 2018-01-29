angular.module('phpdraft.shared').directive('focusOn', $timeout =>
  ({
    restrict: 'A',
    link(scope, element, attrs) {
      return scope.$on(attrs.focusOn, () => $timeout((() => element[0].focus()), 150));
    },
  })
);
