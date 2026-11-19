//From Smart Table docs: http://plnkr.co/edit/ekwiNt?p=preview
/*
  W-38: smart-table resets the page to 1 each time the array of st-safe-src is replaced, and the Drafts
  arrive after the table is linked. Set the saved page again once the Drafts are there. This watcher runs
  after the one of smart-table, because it is registered later.
*/
function restorePageAfterDataLoads(scope, smartTableCtrl, getDrafts, savedStart) {
  if (!savedStart) return;

  const stopWatching = scope.$watch(
    () => getDrafts(scope),
    (drafts, previous) => {
      const hasLoaded = drafts !== previous && drafts && drafts.length > 0;
      if (!hasLoaded) return;

      if (savedStart < drafts.length) {
        smartTableCtrl.tableState().pagination.start = savedStart;
        smartTableCtrl.pipe();
      }
      stopWatching();
    }
  );
}

angular.module('phpdraft.home').directive('phpdPersistTableState', [
  '$sessionStorage',
  '$parse',
  'subscriptionKeys',
  ($sessionStorage, $parse, subscriptionKeys) => ({
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
        // Read it first: extend shares the pagination object, and pipe() on the empty table sets start to 0
        const savedStart = parsedSaveState.pagination && parsedSaveState.pagination.start;

        angular.extend(tableState, parsedSaveState);
        smartTableCtrl.pipe();

        restorePageAfterDataLoads(scope, smartTableCtrl, $parse(attrs.stSafeSrc), savedStart);
      }

      scope.$on(subscriptionKeys.scopeDestroy, () => {
        scope.deregisterPersistTableState();
      });
    },
  }),
]);
