angular.module('phpdraft').factory('draftPasswordHttpRequestInterceptor', $sessionStorage =>
  ({
    request(config) {
      if (($sessionStorage.draft_password != null) && ($sessionStorage.draft_password !== undefined)) {
        if (config.headers == null) { config.headers = {}; }
        config.headers['X-PhpDraft-DraftPassword'] = `${$sessionStorage.draft_password}`;
      }

      return config;
    }
  })
);

angular.module('phpdraft').config($httpProvider => $httpProvider.interceptors.push('draftPasswordHttpRequestInterceptor'));
