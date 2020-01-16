//From Smart Table docs: http://plnkr.co/edit/ekwiNt?p=preview
angular
  .module('phpdraft.home')
  .directive('phpdPersistTableState', $sessionStorage => ({
    restrict: 'A',
    require: '^stTable',
    link: (scope, elment, attrs, smartTableCtrl) => {
      const namespace = attrs.phpdPersistTableState;

      //Save table state every time it changes
      scope.$watch(
        () => smartTableCtrl.tableState(),
        (newValue, oldValue) => {
          if (newValue !== oldValue) {
            $sessionStorage[namespace] = newValue;
          }
        },
        true
      );

      //Load table state when the directive loads
      const savedState = $sessionStorage[namespace]; //TODO: redo with ngStorage
      if (savedState) {
        const parsedSaveState = angular.fromJson(savedState);
        const tableState = smartTableCtrl.tableState();

        angular.extend(tableState, parsedSaveState);
        smartTableCtrl.pipe();
      }
    },
  }));
