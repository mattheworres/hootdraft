angular
  .module('phpdraft.home')
  .directive('phpdTableSortResetButton', ['tableStateService', tableStateService => ({
    restrict: 'E',
    require: '^stTable',
    templateUrl: 'app/features/home/tableSortResetButton.directive.html',
    scope: {
      tableStateHandle: '@',
    },
    link: (scope, element, attrs, smartTableCtrl) => {
      scope.deregisterTableStateChangeWatcher = scope.$watch(
        () => smartTableCtrl.tableState(),
        (newValue, oldValue) => {
          if (newValue !== oldValue) {
            const newDisableButton = !tableStateService.tableStateExists(scope.tableStateHandle);
            if (newDisableButton !== scope.disableButton) {
              scope.disableButton = newDisableButton;
            }
          }
        },
        true
      );

      scope.disableButton = !tableStateService.tableStateExists(scope.tableStateHandle);
      scope.wipeFilters = () => {
        if (scope.disableButton) return;

        let tableState = smartTableCtrl.tableState();//eslint-disable-line
        tableState = tableStateService.wipeStateFilterObject(tableState);

        smartTableCtrl.pipe();
        //Known bug: the st-search input box still displays the value, even
        //though SmartTable no longer filters by it. Gah.
      };
    },
  })]
  );
