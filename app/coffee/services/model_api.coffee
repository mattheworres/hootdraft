###
  Documentation:  https://docs.angularjs.org/api/ngResource/service/$resource
  
  The filename is different because of the alphabetical compilation order
  -----
  These are the default methods $resource provides
  (unless its explicitly overridden below):
  {
    'get':    {method:'GET'},
    'save':   {method:'POST'},
    'query':  {method:'GET', isArray:true},
    'remove': {method:'DELETE'},
    'delete': {method:'DELETE'}
  };
###
angular.module('app').factory 'api', ($resource, ENV) ->
  Authentication: $resource "#{ENV.apiEndpoint}authentication", {}, {
    'login':
      { method: 'POST', url: "#{ENV.apiEndpoint}login" }
    'register':
      { method: 'POST', url: "#{ENV.apiEndpoint}register"}
  }

  Draft: $resource "#{ENV.apiEndpoint}draft/:id", {id: '@id'}, {
    'getDraftList':
      { method: 'GET', url: "#{ENV.apiEndpoint}drafts", isArray: true }
  }