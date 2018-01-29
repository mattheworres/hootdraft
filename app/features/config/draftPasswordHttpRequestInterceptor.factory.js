angular.module('phpdraft.config').factory('draftPasswordHttpRequestInterceptor', $sessionStorage =>
  ({
    request(config) {
      if (($sessionStorage.draft_password !== null) && (angular.isUndefined($sessionStorage.draft_password))) {
        if (config.headers === null) config.headers = {};
        config.headers['X-PhpDraft-DraftPassword'] = `${$sessionStorage.draft_password}`;
      }

      return config;
    },
  })
);

angular.module('phpdraft.config').config($httpProvider => $httpProvider.interceptors.push('draftPasswordHttpRequestInterceptor'));
