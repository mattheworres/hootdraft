angular.module('phpdraft').factory('authTokenHttpRequestInterceptor', $sessionStorage =>
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

angular.module('phpdraft').config($httpProvider => $httpProvider.interceptors.push('authTokenHttpRequestInterceptor'));
