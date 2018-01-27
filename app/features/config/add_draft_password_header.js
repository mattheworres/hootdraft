/*
 * decaffeinate suggestions:
 * DS102: Remove unnecessary code created because of implicit returns
 * DS207: Consider shorter variations of null checks
 * Full docs: https://github.com/decaffeinate/decaffeinate/blob/master/docs/suggestions.md
 */
angular.module('app').factory('draftPasswordHttpRequestInterceptor', $sessionStorage =>
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

angular.module('app').config($httpProvider => $httpProvider.interceptors.push('draftPasswordHttpRequestInterceptor'));