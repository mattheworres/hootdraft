angular.module('phpdraft.home').directive('phpdDistinctTypeahead', ['api', 'messageService', 'limitToFilter',
  (api, messageService, limitToFilter) =>
    ({
      restrict: 'E',
      require: '^^stTable',
      templateUrl: 'app/features/home/distinctTypeahead.directive.html',
      link: (scope, element, attrs, smartTableCtrl) => {
        scope.selectedOption = null;

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
          };

          scope.commishSelected = true;
          scope.commishName = item.name;
          scope.commishId = item.id;

          scope.nameSearch = '';

          //TODO: Branch to wipe search

          smartTableCtrl.search(query, 'commish_id');
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
