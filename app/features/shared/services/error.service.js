class ErrorService {
  constructor($sessionStorage) {
    this.$sessionStorage = $sessionStorage;
  }

  joinErrorsForToastDisplay(errorsArray) {
    return errorsArray.join('      ');
  }

  parseValidationErrorsFromResponse(response) {
    const HTTP_ERROR = 400;

    // A $resource error callback gets the $http response ({status, data: body}). authenticationService wraps that
    // response once more ({status, data: $http response}). Unwrap it, so both give {status, data: body}.
    const isWrapped = angular.isDefined(response) && response !== null &&
      angular.isObject(response.data) && angular.isDefined(response.data.status);
    const httpResponse = isWrapped ? response.data : response;
    const status = angular.isObject(httpResponse) ? httpResponse.status : null;

    if (status !== HTTP_ERROR) {
      return `Whoops! We hit a snag - looks like it's on our end (${status})`;
    }

    const body = httpResponse.data;

    return angular.isObject(body) && angular.isArray(body.errors)
      ? body.errors.join('\n')
      : 'Unknown 400 error';
  }
}

ErrorService.$inject = [
  '$sessionStorage',
];

angular.module('phpdraft.shared').service('errorService', ErrorService);
