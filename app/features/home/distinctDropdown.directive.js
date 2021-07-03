angular
  .module('phpdraft.home')
  .directive('phpdDistinctDropdown', ['tableStateService', tableStateService => ({
    restrict: 'E',
    require: '^^stTable',
    templateUrl: 'app/features/home/distinctDropdown.directive.html',
    scope: {
      listItems: '=',
      rowPropertyName: '@',
      useValueDisplay: '=',
      labelType: '@',
    },
    link: (scope, element, attrs, smartTableCtrl) => {
      scope.selectedOption = null;

      const initializeStateCallback = (distinct, display) => {
        scope.selectedOption = distinct;
        scope.selectedValue = display;
      };

      tableStateService.initializeFilterComponentFromStorage(attrs.tableStateHandle, scope.rowPropertyName, initializeStateCallback);

      scope.labelSelectDistinctChanged = (selectedOption, selectedValue) => {
        const query = {
          distinct: selectedOption,
          display: selectedValue,
        };

        scope.selectedOption = selectedOption;
        scope.selectedValue = selectedValue;

        if (query.distinct === 'remove') {
          scope.selectedOption = null;
          query.distinct = '';
        }

        smartTableCtrl.search(query, scope.rowPropertyName);
      };
    },
  })]
  );
