class AuthenticationService {
  constructor(api, $q, $sessionStorage, messageService, pathHelperService) {
    this.api = api;
    this.$q = $q;
    this.$sessionStorage = $sessionStorage;
    this.messageService = messageService;
    this.pathHelperService = pathHelperService;
  }

  cacheSession(userData) {
    this.$sessionStorage.authenticated = true;
    this.$sessionStorage.auth_token = userData.token;
    this.$sessionStorage.user_name = userData.name;
    this.$sessionStorage.auth_time = userData.auth_timeout;
    this.$sessionStorage.user_admin = userData.is_admin;
  }

  cacheName(userName) {
    delete this.$sessionStorage.user_name;
    this.$sessionStorage.user_name = userName;
  }

  uncacheSession() {
    this.$sessionStorage.authenticated = false;
    delete this.$sessionStorage.auth_token;
    delete this.$sessionStorage.user_name;
    delete this.$sessionStorage.auth_time;
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
      (this.$sessionStorage.user_name !== null) &&
      (this.$sessionStorage.auth_token !== null);
  }

  currentUserName() {
    return this.$sessionStorage.user_name;
  }

  isAdmin() {
    return this.$sessionStorage.user_admin;
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

    const auth_time = new Date(this.$sessionStorage.auth_time);
    const now = new Date();

    if (now > auth_time) {
      this.uncacheSession();
      return true;
    }

    return false;
  }
  // TODO: Dry these methods up?
  register(model) {
    const result = this.$q.defer();
    const successHandler = data => result.resolve({data, status: data.status});
    const errorHandler = data => result.reject({data, status: data.status});
    this.api.Authentication.register(model, successHandler, errorHandler);

    return result;
  }

  verify(model) {
    const result = this.$q.defer();
    const successHandler = data => result.resolve({data, status: data.status});
    const errorHandler = data => result.reject({data, status: data.status});
    this.api.Authentication.verify(model, successHandler, errorHandler);

    return result;
  }

  lostPassword(model) {
    const result = this.$q.defer();
    const successHandler = data => result.resolve({data, status: data.status});
    const errorHandler = data => result.reject({data, status: data.status});
    this.api.Authentication.lostPassword(model, successHandler, errorHandler);

    return result;
  }

  verifyResetToken(model) {
    const result = this.$q.defer();
    const successHandler = data => result.resolve({data, status: data.status});
    const errorHandler = data => result.reject({data, status: data.status});
    this.api.Authentication.verifyResetToken(model, successHandler, errorHandler);

    return result;
  }

  resetPassword(model) {
    const result = this.$q.defer();
    const successHandler = data => result.resolve({data, status: data.status});
    const errorHandler = data => result.reject({data, status: data.status});
    this.api.Authentication.resetPassword(model, successHandler, errorHandler);

    return result;
  }
}

AuthenticationService.$inject = [
  'api',
  '$q',
  '$sessionStorage',
  'messageService',
  'pathHelperService',
];

angular.module('phpdraft.shared').service('authenticationService', AuthenticationService);
