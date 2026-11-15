// A route resolve for pages that check "is authenticated" once when they load. A fresh tab has nothing cached, so
// the page waits for the session check at startup before it loads.
angular.module('phpdraft.shared').constant('sessionCheckedResolve', {
  sessionChecked: ['authenticationService', authenticationService => authenticationService.sessionChecked()],
});
