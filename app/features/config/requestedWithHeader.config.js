// Authentication is an HttpOnly session cookie the browser sends by itself, so there is no token to attach.
// The API does require this header on every request that can change state: a custom header can only be set
// by same-origin scripts, which keeps forged cross-site requests out (see CsrfHeaderListener on the server).
angular.module('phpdraft.config').config($httpProvider => {
  $httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
});
