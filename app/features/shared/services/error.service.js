class ErrorService {
  constructor($sessionStorage) {
    this.$sessionStorage = $sessionStorage;
  }

  joinErrorsForToastDisplay(errorsArray) {
    return errorsArray.join('      ');
  }

  parseValidationErrorsFromResponse(response) {
    const HTTP_ERROR = 400;
    let error = '';

    if (angular.isDefined(response) && angular.isDefined(response.data) && response.data.status === HTTP_ERROR) {
      error = angular.isDefined(response.data.data) &&
        angular.isDefined(response.data.data.errors)
        ? response.data.data.errors.join('\n')
        : 'Unknown 400 error';
    } else {
      error = `Whoops! We hit a snag - looks like it's on our end (${response.data.status})`;
    }

    return error;
  }
}

ErrorService.$inject = [
  '$sessionStorage',
];

angular.module('phpdraft.shared').service('errorService', ErrorService);
