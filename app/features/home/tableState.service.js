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

    if (!this.rowPropertyExists(tableStateHandle, rowPropertyName)) return;

    const parsedSaveState = this.parseTableState(tableStateHandle);

    const distinct = parsedSaveState.search.predicateObject[rowPropertyName].distinct;
    const display = parsedSaveState.search.predicateObject[rowPropertyName].display;

    //Call the callback with the stored data
    initCallback(distinct, display);
  }

  /*
    Determine if table state exists (filters are applied)
  */
  tableStateExists(tableStateHandle) {
    const savedState = this.$sessionStorage[tableStateHandle];
    console.log(`Hey, asked about existing table state for ${tableStateHandle}`);

    if (!savedState) {
      return false;
    }

    const parsedSaveState = angular.fromJson(savedState);
    const hasSearch = parsedSaveState.search && Boolean(parsedSaveState.search.predicateObject);

    if (!hasSearch) {
      return false;
    }

    if (hasSearch) {
      let hasOneSubkey = false;

      //Verify that predicate has at least 1 object with sub-keys
      Object.keys(parsedSaveState.search.predicateObject).forEach(key => {
        const predicate = parsedSaveState.search.predicateObject[key];
        if (Object.keys(predicate).length) hasOneSubkey = true;
      });

      return hasOneSubkey;
    }

    return true;
  }

  parseTableState(tableStateHandle) {
    return angular.fromJson(this.$sessionStorage[tableStateHandle]);
  }

  /*
    Determine if a specific property exists in table state
    (also calls tableStateExists)
  */
  rowPropertyExists(tableStateHandle, rowPropertyName) {
    if (this.tableStateExists(tableStateHandle) === false) return false;

    const parsedSaveState = this.parseTableState(tableStateHandle);

    const propertyExists = parsedSaveState.search &&
      parsedSaveState.search.predicateObject &&
      parsedSaveState.search.predicateObject[rowPropertyName] &&
      parsedSaveState.search.predicateObject[rowPropertyName].distinct;

    return Boolean(propertyExists);
  }

  /*
    Given the predicate object, ensure all children
    have empty distinct properties
  */
  wipeStateFilterObject(tableState) {
    tableState.search = {};

    return tableState;
  }
}

TableStateService.$inject = [
  '$sessionStorage',
];

angular.module('phpdraft.home').service('tableStateService', TableStateService);
