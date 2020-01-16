class TableStateService {
  constructor($sessionStorage) {
    this.$sessionStorage = $sessionStorage;
  }

  /*
    Encapsulated logic that initializes a single column component upon page render
    from session storage, and calls the provided callback if there is valid savestate
  */
  initializeFilterComponentFromStorage(tableStateHandle, rowPropertyName, initCallback) {
    //If we havent been given a tableStateHandle just stop here
    if (!tableStateHandle) return;

    //If we havent been given a callback just stop here
    if (!initCallback ||
      !angular.isFunction(initCallback)) return;

    const savedState = this.$sessionStorage[tableStateHandle];

    //If no saved state object from storage exists, stop here
    if (!savedState) return;

    const parsedSaveState = angular.fromJson(savedState);

    //If any of these properties don't exist, we can also stop here
    if (!parsedSaveState.search ||
      !parsedSaveState.search.predicateObject ||
      !parsedSaveState.search.predicateObject[rowPropertyName] ||
      !parsedSaveState.search.predicateObject[rowPropertyName].distinct) {
      return;
    }

    const distinct = parsedSaveState.search.predicateObject[rowPropertyName].distinct;
    const display = parsedSaveState.search.predicateObject[rowPropertyName].display;

    //Call the callback with the stored data
    initCallback(distinct, display);
  }
}

TableStateService.$inject = [
  '$sessionStorage',
];

angular.module('phpdraft.home').service('tableStateService', TableStateService);
