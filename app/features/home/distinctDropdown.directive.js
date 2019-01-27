angular.module('phpdraft.home').directive('phpdDistinctDropdown', () =>
  ({
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

      scope.labelSelectDistinctChanged = (selectedOption, selectedValue) => {
        const query = {
          distinct: selectedOption,
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
  })
);
