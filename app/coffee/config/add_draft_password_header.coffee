angular.module('app').factory 'draftPasswordHttpRequestInterceptor', ($sessionStorage) ->
  request: (config) ->
    if $sessionStorage.draft_password? and $sessionStorage.draft_password != undefined
      config.headers ?= {}
      config.headers['X-PhpDraft-DraftPassword'] = "#{$sessionStorage.draft_password}"

    config

angular.module('app').config ($httpProvider) ->
  $httpProvider.interceptors.push('draftPasswordHttpRequestInterceptor');