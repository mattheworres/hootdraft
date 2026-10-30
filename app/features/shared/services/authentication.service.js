class AuthenticationService {
  constructor(api, $q, $sessionStorage, $location, messageService, pathHelperService) {
    this.api = api;
    this.$q = $q;
    this.$sessionStorage = $sessionStorage;
    this.$location = $location;
    this.messageService = messageService;
    this.pathHelperService = pathHelperService;
  }

  cacheSession(userData) {
    this.$sessionStorage.authenticated = true;
    this.$sessionStorage.userName = userData.name;
    this.$sessionStorage.userAdmin = userData.is_admin;
  }

  cacheName(userName) {
    delete this.$sessionStorage.userName;
    this.$sessionStorage.userName = userName;
  }

  uncacheSession() {
    this.$sessionStorage.authenticated = false;
    delete this.$sessionStorage.userName;
    delete this.$sessionStorage.userAdmin;
  }

  cacheRoles(roles) {
    this.$sessionStorage.roles = roles;
  }

  sendAuthenticatedUserToPreviousPath() {
    this.messageService.showInfo(`Already logged in as ${this.currentUserName()}.`, 'Logged In');
    this.pathHelperService.sendToPreviousPath();
  }

  isAuthenticated() {
    // Only a hint for the UI: the real login is the session cookie, which JavaScript can't see.
    // verifySession() checks it with the server.
    return this.$sessionStorage.authenticated &&
      (this.$sessionStorage.userName !== null);
  }

  currentUserName() {
    return this.$sessionStorage.userName;
  }

  isAdmin() {
    return this.$sessionStorage.userAdmin;
  }

  login(model) {
    const result = this.$q.defer();

    this.uncacheSession();

    const successHandler = data => {
      this.cacheSession(data);
      return result.resolve({
        data,
        status: data.status,
      });
    };

    const errorHandler = data => result.reject({data, status: data.status});

    this.api.Authentication.login(model, successHandler, errorHandler);

    return result;
  }

  logout() {
    // Ends the session cookie on the server; the local state is cleared right away either way
    this.api.Authentication.logout(angular.noop, angular.noop);
    return this.uncacheSession();
  }

  // Ask the server whether the cookie still logs us in. sessionStorage is per tab while the cookie is shared by
  // all of them, so a new tab can be logged in with nothing cached: always ask, and pick the user up if so.
  verifySession() {
    const wasAuthenticated = this.isAuthenticated();

    this.api.Authentication.me(data => {
      this.cacheSession(data);
    }, response => {
      if (response.status === 401 && wasAuthenticated) {
        this.handleSessionExpired();
      }
    });
  }

  // The server says the session is over although this tab thinks we are logged in
  handleSessionExpired() {
    if (!this.isAuthenticated()) {
      return;
    }

    this.uncacheSession();
    this.messageService.showInfo('Your login has expired. You must log back in if you wish to continue.');
    if (this.$location.path() !== '/login') {
      this.$location.path('/login');
    }
  }

  register(model) {
    return this._makeApiCall(model, this.api.Authentication.register);
  }

  emailVerification(model) {
    return this._makeApiCall(model, this.api.Authentication.emailVerification);
  }

  lostPassword(model) {
    return this._makeApiCall(model, this.api.Authentication.lostPassword);
  }

  checkResetKey(model) {
    return this._makeApiCall(model, this.api.Authentication.checkResetKey);
  }

  resetPassword(model) {
    return this._makeApiCall(model, this.api.Authentication.resetPassword);
  }

  handleUnauthorizedResponse(response) {
    if ((angular.isDefined(response) ? response.status : 0) === 401) {
      this.messageService.showError('Unauthorized: please log in.');
      this.uncacheSession();
      this.$location.path('/login');
      return true;
    }

    return false;
  }

  _makeApiCall(model, methodCall) {
    const result = this.$q.defer();
    const successHandler = data => result.resolve({data, status: data.status});
    const errorHandler = data => result.reject({data, status: data.status});
    methodCall(model, successHandler, errorHandler);

    return result;
  }
}

AuthenticationService.$inject = [
  'api',
  '$q',
  '$sessionStorage',
  '$location',
  'messageService',
  'pathHelperService',
];

angular.module('phpdraft.shared').service('authenticationService', AuthenticationService);
