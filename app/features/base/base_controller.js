class BaseController { // eslint-disable-line no-unused-vars
  //T0DO: this needs implemented in a shared service since BaseController will go away
  sendToPreviousPath() {
    const storedPreviousRoute = this.$sessionStorage.previousRoutes.splice(-2)[0];
    if ((this.$sessionStorage.previousRoutes.length > 1) && !this.pathIsWhitelisted(storedPreviousRoute)) {
      this.$location.path(storedPreviousRoute);
    } else {
      this.$location.path('/home');
    }
  }

  //T0DO: This goes with sendPreviousPath() above
  pathIsWhitelisted(path) {
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

  //T0DO: This needs implemented on draftState component
  showDraftPasswordModal() {
    return this.draftService.showPasswordModal();
  }
}
