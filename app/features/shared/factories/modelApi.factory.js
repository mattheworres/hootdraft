/*
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
*/
angular.module('phpdraft.shared').factory('api', ($resource, ENV) =>
  ({
    Authentication: $resource(`${ENV.apiEndpoint}authentication`, {}, {
      'login':
        {method: 'POST', url: `${ENV.apiEndpoint}login`},
      'register':
        {method: 'POST', url: `${ENV.apiEndpoint}register`},
      'verify':
        {method: 'POST', url: `${ENV.apiEndpoint}verify`},
      'verifyResetToken':
        {method: 'GET', url: `${ENV.apiEndpoint}verifyToken`},
      'lostPassword':
        {method: 'POST', url: `${ENV.apiEndpoint}lostPassword`},
      'resetPassword':
        {method: 'POST', url: `${ENV.apiEndpoint}resetPassword`},
      'getProfile':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/profile`},
      'setProfile':
        {method: 'PUT', url: `${ENV.apiEndpoint}commish/profile`},
      'invite':
        {method: 'POST', url: `${ENV.apiEndpoint}commish/user/invite`},
    }),
    // TODO: Rename all snake_case variables here and in frontend to camelCase
    Commish: $resource(`${ENV.apiEndpoint}commissioners/:commish_id`, {commish_id: '@commish_id'}, {
      'search':
        {method: 'GET', url: `${ENV.apiEndpoint}commissioners/search`},
    }),

    Admin: $resource(`${ENV.apiEndpoint}admin`, {draft_id: '@draft_id'}, {
      'getSports':
        {method: 'GET', url: `${ENV.apiEndpoint}admin/sports`},
      'getAllDrafts':
        {method: 'GET', url: `${ENV.apiEndpoint}admin/drafts`, isArray: true},
      'regenerateDraftStats':
        {method: 'POST', url: `${ENV.apiEndpoint}admin/draft/:draft_id/stats`},
    }),

    Draft: $resource(`${ENV.apiEndpoint}draft/:id`, {draft_id: '@draft_id'}, {
      'getDraftList':
        {method: 'GET', url: `${ENV.apiEndpoint}drafts`, isArray: true},
      'searchCommissioners':
        {method: 'GET', url: `${ENV.apiEndpoint}drafts/commissioners/search`},
      'getDraftsByCommish':
        {method: 'GET', url: `${ENV.apiEndpoint}drafts/:commish_id`, isArray: true},
      'getStats':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/stats`},
      'getTimeRemaining': ///draft/{draft_id}/timer/remaining
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/timer/remaining`},

      'getCreate':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/draft/create`},
      save:
        {method: 'POST', url: `${ENV.apiEndpoint}commish/draft`},
      'update':
        {method: 'PUT', url: `${ENV.apiEndpoint}commish/draft/:draft_id`},
      'commishGet':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/draft/:draft_id`},
      delete:
        {method: 'DELETE', url: `${ENV.apiEndpoint}commish/draft/:draft_id`},
      'updateStatus':
        {method: 'PUT', url: `${ENV.apiEndpoint}commish/draft/:draft_id/status`},
      'getTimers':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/draft/:draft_id/timers`, isArray: true},
      'setTimers':
        {method: 'POST', url: `${ENV.apiEndpoint}commish/draft/:draft_id/timers`},
    }),

    DepthChartPosition: $resource(`${ENV.apiEndpoint}commish/draft/:id/depthchartposition/:position_id`,
      {draft_id: '@draft_id', position_id: '@position_id', draft_sport: '@draft_sport', manager_id: '@manager_id', pick_id: '@pick_id'}, {
        'getDepthChart':
          {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/manager/:manager_id/depth_chart`},
        'getPositions':
          {method: 'GET', url: `${ENV.apiEndpoint}commish/depthchartposition/positions`},
        'update':
          {method: 'PUT', url: `${ENV.apiEndpoint}draft/:draft_id/pick/:pick_id/depth_chart/:position_id`},
      }),

    Manager: $resource(`${ENV.apiEndpoint}draft/:draft_id/manager/:manager_id`, {draft_id: '@draft_id', manager_id: '@manager_id'}, {
      'getManagers':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/managers`, isArray: true},
      'commishGet':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/draft/:draft_id/managers`, isArray: true},
      'reorder':
        {method: 'PUT', url: `${ENV.apiEndpoint}commish/draft/:draft_id/managers/reorder`},
      'addMultiple':
        {method: 'POST', url: `${ENV.apiEndpoint}commish/draft/:draft_id/managers`},
      delete:
        {method: 'DELETE', url: `${ENV.apiEndpoint}commish/draft/:draft_id/manager/:manager_id`},
      'update':
        {method: 'PUT', url: `${ENV.apiEndpoint}commish/draft/:draft_id/manager/:manager_id`},
    }),

    Pick: $resource(`${ENV.apiEndpoint}draft/:draft_id/pick/:pick_id`, {draft_id: '@draft_id', player_id: '@player_id'}, {
      'getAll':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/picks`},
      'getUpdated':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/picks/updated`},
      'getLast':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/picks/last`, isArray: true},
      'getNext':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/picks/next`, isArray: true},
      'getSelectedByRound':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/round/:round/picks/selected`, isArray: true},
      'getAllByRound':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/round/:round/picks/all`, isArray: true},
      'getAllByManager':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/manager/:manager_id/picks/all`, isArray: true},
      'search':
        {method: 'GET', url: `${ENV.apiEndpoint}draft/:draft_id/picks/search`},

      'getCurrent':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/draft/:draft_id/pick/current`},
      'searchProPlayers':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/proplayers/search`},
      'alreadyDrafted':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/draft/:draft_id/picks/alreadyDrafted`},
      'add': //Fun gotcha: table still references "player_id", so we must use that both here and on API side :)
        {method: 'POST', url: `${ENV.apiEndpoint}commish/draft/:draft_id/pick/:player_id`},
      'update':
        {method: 'PUT', url: `${ENV.apiEndpoint}commish/draft/:draft_id/pick/:player_id`},
    }),

    Trade: $resource(`${ENV.apiEndpoint}draft/:draft_id/trades`, {draft_id: '@draft_id', manager_id: '@manager_id'}, {
      'getAssets':
        {method: 'GET', url: `${ENV.apiEndpoint}commish/draft/:draft_id/manager/:manager_id/assets`},
      save:
        {method: 'POST', url: `${ENV.apiEndpoint}commish/draft/:draft_id/trade`},
    }),

    User: $resource(`${ENV.apiEndpoint}admin/user/:id`, {id: '@id'}, {
      'getAll':
        {method: 'GET', url: `${ENV.apiEndpoint}admin/users`},
      'update':
        {method: 'PUT', url: `${ENV.apiEndpoint}admin/user/:id`},
      delete:
        {method: 'DELETE'},
    }),

    Resources: $resource(`${ENV.apiEndpoint}`, {id: '@id'}, {
      'draftOptions':
        {method: 'GET', url: `${ENV.apiEndpoint}draftOptions`},
    }),
  })
);
