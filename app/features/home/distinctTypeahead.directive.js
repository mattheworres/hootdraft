/* Note: this directive is named generically, but realistically
    genericizing the code when I did not have another use for it
    seemed like overkill (and does again still). So there are
    ways of genericizing this for it to truly be use-agnostic,
    but for now this puppy is hard-coded for commissioner selection
    only
*/
angular.module('phpdraft.home').directive('phpdDistinctTypeahead', [
  'api',
  'messageService',
  'limitToFilter',
  'tableStateService',
  (api, messageService, limitToFilter, tableStateService) =>
    ({
      restrict: 'E',
      require: '^^stTable',
      templateUrl: 'app/features/home/distinctTypeahead.directive.html',
      scope: {
        rowPropertyName: '@',
      },
      link: (scope, element, attrs, smartTableCtrl) => {
        scope.selectedOption = null;

        const initializeStateCallback = (distinct, display) => {
          scope.commishSelected = true;
          scope.commishName = display;
          scope.commishId = distinct;
        };

        tableStateService.initializeFilterComponentFromStorage(attrs.tableStateHandle, scope.rowPropertyName, initializeStateCallback);

        scope.searchCommisioners = searchTerm => {
          scope.commishSearchLoading = true;
          return api.Commish.search({searchTerm}).$promise
            .then(data => {
              scope.commishSearchLoading = false;
              return limitToFilter(data.commissioners, 10);
            }).catch(() => {
              scope.commishSearchLoading = false;
              messageService.closeToasts();
              messageService.showError('Unable to search commissioners');
            });
        };

        scope.selectCommissioner = item => {
          const query = {
            distinct: item.id,
            display: item.name,
          };

          scope.commishSelected = true;
          scope.commishName = item.name;
          scope.commishId = item.id;

          scope.nameSearch = '';

          smartTableCtrl.search(query, scope.rowPropertyName);
        };

        scope.wipeCommissioner = () => {
          const query = {
            distinct: '',
          };

          scope.commishSelected = false;
          scope.commishName = null;
          scope.commishId = null;

          smartTableCtrl.search(query, 'commish_id');
        };
      },
    })]
);
