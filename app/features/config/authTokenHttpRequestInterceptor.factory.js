angular.module('phpdraft.config').factory('authTokenHttpRequestInterceptor', $sessionStorage =>
  ({
    request(config) {
      if ($sessionStorage.authenticated) {
        if (config.headers === null) config.headers = {};
        config.headers['X-Access-Token'] = `${$sessionStorage.authToken}`;
      }

      return config;
    },
  })
);

angular.module('phpdraft.config').config($httpProvider => $httpProvider.interceptors.push('authTokenHttpRequestInterceptor'));
