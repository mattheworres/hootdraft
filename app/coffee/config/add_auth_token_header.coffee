angular.module('app').factory 'authTokenHttpRequestInterceptor', ($sessionStorage) ->
    request: (config) ->
        if $sessionStorage.authenticated
            config.headers ?= {}
            config.headers['X-Access-Token'] = "#{sessionStorage.auth_token}"

        config

angular.module('app').config ($httpProvider) ->
  $httpProvider.interceptors.push('authTokenHttpRequestInterceptor');