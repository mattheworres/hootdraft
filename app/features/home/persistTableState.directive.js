//From Smart Table docs: http://plnkr.co/edit/ekwiNt?p=preview
angular.module('phpdraft.home').directive('phpdPersistTableState', [
  '$sessionStorage',
  'subscriptionKeys',
  ($sessionStorage, subscriptionKeys) => ({
    restrict: 'A',
    require: '^stTable',
    link: (scope, element, attrs, smartTableCtrl) => {
      const namespace = attrs.phpdPersistTableState;

      //Save table state every time it changes
      scope.deregisterPersistTableState = scope.$watch(
        () => smartTableCtrl.tableState(),
        (newValue, oldValue) => {
          if (newValue !== oldValue) {
            $sessionStorage[namespace] = newValue;
          }
        },
        true
      );

      //Load table state when the directive loads
      const savedState = $sessionStorage[namespace];
      if (savedState) {
        const parsedSaveState = angular.fromJson(savedState);
        const tableState = smartTableCtrl.tableState();

        angular.extend(tableState, parsedSaveState);
        smartTableCtrl.pipe();
      }

      scope.$on(subscriptionKeys.scopeDestroy, () => {
        scope.deregisterPersistTableState();
      });
    },
  }),
]);
