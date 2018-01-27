angular.module('app').factory('authTokenHttpRequestInterceptor', $sessionStorage =>
    ({
        request(config) {
            if ($sessionStorage.authenticated) {
                if (config.headers == null) { config.headers = {}; }
                config.headers['X-Access-Token'] = `${$sessionStorage.auth_token}`;
            }

            return config;
        }
    })
);

angular.module('app').config($httpProvider => $httpProvider.interceptors.push('authTokenHttpRequestInterceptor'));
