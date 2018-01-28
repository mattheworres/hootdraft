class ErrorService {
  constructor($sessionStorage) {
    this.$sessionStorage = $sessionStorage;
  }

  joinErrorsForToastDisplay(errorsArray) {
    return errorsArray.join('      ');
  }
}

ErrorService.$inject = [
  '$sessionStorage'
];

angular.module('phpdraft').service('errorService', ErrorService);
