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
    this.$sessionStorage.authToken = userData.token;
    this.$sessionStorage.userName = userData.name;
    this.$sessionStorage.authTime = userData.auth_timeout;
    this.$sessionStorage.userAdmin = userData.is_admin;
  }

  cacheName(userName) {
    delete this.$sessionStorage.userName;
    this.$sessionStorage.userName = userName;
  }

  uncacheSession() {
    this.$sessionStorage.authenticated = false;
    delete this.$sessionStorage.authToken;
    delete this.$sessionStorage.userName;
    delete this.$sessionStorage.authTime;
  }

  cacheRoles(roles) {
    this.$sessionStorage.roles = roles;
  }

  sendAuthenticatedUserToPreviousPath() {
    this.messageService.showInfo(`Already logged in as ${this.currentUserName()}.`, 'Logged In');
    this.pathHelperService.sendToPreviousPath();
  }

  isAuthenticated() {
    return this.$sessionStorage.authenticated &&
      (this.$sessionStorage.userName !== null) &&
      (this.$sessionStorage.authToken !== null);
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
    return this.uncacheSession();
  }

  isAuthenticationExpired() {
    if (!this.isAuthenticated()) {
      return false;
    }

    const authTime = new Date(this.$sessionStorage.authTime);
    const now = new Date();

    if (now > authTime) {
      this.uncacheSession();
      return true;
    }

    return false;
  }

  register(model) {
    return this._makeApiCall(model, this.api.Authentication.register);
  }

  verify(model) {
    return this._makeApiCall(model, this.api.Authentication.verify);
  }

  lostPassword(model) {
    return this._makeApiCall(model, this.api.Authentication.lostPassword);
  }

  verifyResetToken(model) {
    return this._makeApiCall(model, this.api.Authentication.verifyResetToken);
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
