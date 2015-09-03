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

  Commish: $resource "#{ENV.apiEndpoint}commissioners/:commish_id", { commish_id: '@commish_id' },
    'search':
      { method: 'GET', url: "#{ENV.apiEndpoint}commissioners/search" }

  Draft: $resource "#{ENV.apiEndpoint}draft/:id", { draft_id: '@draft_id' },
    'getDraftList':
      { method: 'GET', url: "#{ENV.apiEndpoint}drafts", isArray: true }
    'searchCommissioners':
      { method: 'GET', url: "#{ENV.apiEndpoint}drafts/commissioners/search" }
    'getDraftsByCommish':
      { method: 'GET', url: "#{ENV.apiEndpoint}drafts/:commish_id", isArray: true}
    'getStats':
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/stats" }

    'getCreate':
      { method: 'GET', url: "#{ENV.apiEndpoint}commish/draft/create" }
    save:
      { method: 'POST', url: "#{ENV.apiEndpoint}commish/draft" }
    'update':
      { method: 'PUT', url: "#{ENV.apiEndpoint}commish/draft/:draft_id" }
    'commishGet':
      { method: 'GET', url: "#{ENV.apiEndpoint}commish/draft/:draft_id" }

  Manager: $resource "#{ENV.apiEndpoint}draft/:draft_id/manager/:manager_id", { draft_id: '@draft_id', manager_id: '@manager_id' },
    'getManagers':
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/managers", isArray: true }
    'commishGet':
      { method: 'GET', url: "#{ENV.apiEndpoint}commish/draft/:draft_id/managers", isArray: true }
    'reorder':
      { method: 'PUT', url: "#{ENV.apiEndpoint}commish/draft/:draft_id/managers/reorder" }
    'addMultiple':
      { method: 'POST', url: "#{ENV.apiEndpoint}commish/draft/:draft_id/managers" }

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
    'search': 
      { method: 'GET', url: "#{ENV.apiEndpoint}draft/:draft_id/picks/search" }

  Trade: $resource "#{ENV.apiEndpoint}draft/:draft_id/trades", { draft_id: '@draft_id' },
    