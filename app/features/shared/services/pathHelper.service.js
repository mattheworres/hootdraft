class PathHelperService {
  constructor($sessionStorage, $location) {
    this.$sessionStorage = $sessionStorage;
    this.$location = $location;
  }

  sendToPreviousPath() {
    const storedPreviousRoute = angular.isDefined(this.$sessionStorage.previousRoutes)
      ? this.$sessionStorage.previousRoutes.splice(-2)[0]
      : null;
    if (storedPreviousRoute !== null &&
      this.$sessionStorage.previousRoutes.length > 1 &&
      !this._pathIsWhitelisted(storedPreviousRoute)) {
      this.$location.path(storedPreviousRoute);
    } else {
      this.$location.path('/home');
    }
  }

  _pathIsWhitelisted(path) {
    const whitelistedPaths = [
      '/login',
      '/verify',
      '/resetPassword',
      '/forgotPassword',
      '/register',
      '/profile',
    ];

    return whitelistedPaths.some(whitelistedPath => path.indexOf(whitelistedPath) !== -1);
  }
}

PathHelperService.$inject = [
  '$sessionStorage',
  '$location',
];

angular.module('phpdraft.shared').service('pathHelperService', PathHelperService);
