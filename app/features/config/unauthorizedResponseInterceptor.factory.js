// The login is a session cookie that can end without the page knowing (it expires, the server drops it, the
// account is disabled). Any API call then comes back 401, so catch that in one place instead of per call site.
// authenticationService is looked up lazily: it needs $resource, which needs $http, which needs this interceptor.
angular.module('phpdraft.config').factory('unauthorizedResponseInterceptor', ($q, $injector, ENV) =>
  ({
    responseError: response => {
      const url = response.config ? response.config.url : '';
      // A 401 from these is an answer to the question asked (wrong password, nobody logged in), not an expiry
      const isAuthProbe = url === `${ENV.apiEndpoint}login` || url === `${ENV.apiEndpoint}me`;

      if (response.status === 401 && !isAuthProbe) {
        $injector.get('authenticationService').handleSessionExpired();
      }

      return $q.reject(response);
    },
  }));

angular.module('phpdraft.config').config($httpProvider => $httpProvider.interceptors.push('unauthorizedResponseInterceptor'));
