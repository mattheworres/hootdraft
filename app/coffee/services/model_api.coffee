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
  Authentication: $resource "#{ENV.apiEndpoint}authentication", {},
    'login':
      { method: 'POST', url: "#{ENV.apiEndpoint}login" }
    'register':
      { method: 'POST', url: "#{ENV.apiEndpoint}register" }
    'verify':
      { method: 'POST', url: "#{ENV.apiEndpoint}verify" }
    'verifyResetToken':
      { method: 'GET', url: "#{ENV.apiEndpoint}verifyToken" }
    'lostPassword':
      { method: 'POST', url: "#{ENV.apiEndpoint}lostPassword" }
    'resetPassword':
      { method: 'POST', url: "#{ENV.apiEndpoint}resetPassword" }

  Draft: $resource "#{ENV.apiEndpoint}draft/:id", {id: '@id'},
    'getDraftList':
      { method: 'GET', url: "#{ENV.apiEndpoint}drafts", isArray: true }

  Manager: $resource "#{ENV.apiEndpoint}draft/:draft_id/manager/:manager_id", { draft_id: '@draft_id', manager_id: '@manager_id' },
    'getManagers':
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/managers", isArray: true }

  Pick: $resource "#{ENV.apiEndpoint}draft/:draft_id/pick/:pick_id", { draft_id: '@draft_id', pick_id: '@pick_id' },
    'getLast':
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/picks/last", isArray: true }
    'getNext':
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/picks/next", isArray: true }
    'getSelectedByRound':
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/round/:round/picks/selected", isArray: true }
    'getAllByRound':
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/round/:round/picks/all", isArray: true }
    'getAllByManager':
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/manager/:manager_id/picks/all", isArray: true }