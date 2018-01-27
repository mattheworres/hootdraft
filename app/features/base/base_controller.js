const Cls = (this.BaseController = class BaseController extends AngularController {
  static initClass() {
    //default dependencies in case @inject is never called from the child
    this.$inject = ["$routeParams", "$scope", "$rootScope", "$location", "$sessionStorage",
    "$window", "authenticationService", "messageService", "donationPromptService",
    "draftService", "subscriptionKeys", "DTOptionsBuilder"];
  }
  static inject(...args) {
      args.push('$routeParams');
      args.push('$scope');
      args.push('$rootScope');
      args.push('$location');
      args.push('$sessionStorage');
      args.push('$window');
      args.push('authenticationService');
      args.push('messageService');
      args.push('donationPromptService');
      args.push('draftService');
      args.push('subscriptionKeys');
      args.push('DTOptionsBuilder');
      args.push('api');
      return super.inject(...Array.from(args || []));
    }

  constructor() {
    super(...arguments);
    if (typeof this.initialize === 'function') {
      this.initialize();
    }
  }

  isAuthenticated() {
    return this.authenticationService.isAuthenticated();
  }

  isAdmin() {
    return this.isAuthenticated() && this.authenticationService.isAdmin();
  }

  authenticatedName() {
    return this.$sessionStorage.user_name;
  }

  logOut() {
    this.authenticationService.logout();
    this.messageService.showInfo("Logged Out");
    this.$location.path('/home');
  }

  hideFooter() {
    return (this.$location.$$path.indexOf('/board') !== -1) ||
      (this.$location.$$path.indexOf('/depth_chart') !== -1);
  }

  sendToPreviousPath() {
    const storedPreviousRoute = this.$sessionStorage.previousRoutes.splice(-2)[0];
    if ((this.$sessionStorage.previousRoutes.length > 1) && !this._pathIsWhitelisted(storedPreviousRoute)) {
      this.$location.path(storedPreviousRoute);
    } else {
      this.$location.path('/home');
    }
  }

  showDraftPasswordModal() {
    return this.draftService.showPasswordModal();
  }

  defaultDatatablesOptions() {
    return this.DTOptionsBuilder
        .withPaginationType('simple')
        .newOptions()
        .withDisplayLength(25)
        .withBootstrap()
        .withBootstrapOptions({
            ColVis: {
                classes: {
                    masterButton: 'btn btn-primary'
                }
            }
          })
        .withColVis();
  }

  _pathIsWhitelisted(path) {
    const whitelisted_paths = [
      '/login',
      '/verify',
      '/resetPassword',
      '/forgotPassword',
      '/register',
      '/profile'
    ];

    return whitelisted_paths.some(whitelisted_path => ~path.indexOf(whitelisted_path));
  }
});
Cls.initClass();
