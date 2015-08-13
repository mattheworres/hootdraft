class DraftPasswordModalController extends AngularController
  @register 'DraftPasswordModalController'
  @inject '$modalInstance',
  '$rootScope',
  '$sessionStorage',
  '$routeParams',
  'authenticationService',
  'subscriptionKeys',
  '$location'

  initialize: =>
    super()
    @password = @$sessionStorage.draft_password

  isUnauthenticated: ->
    not @authenticationService.isAuthenticated()

  setPassword: =>
    @$sessionStorage.draft_password = @form.password.$viewValue

    @$rootScope.$broadcast(@subscriptionKeys.reloadDraft, { draft_id: @$routeParams.draft_id });
    @cancel()

  gotoLogin: =>
    @$location.path 'login'
    @cancel()

  # Helpers #
  cancel: =>
    @$modalInstance.dismiss('closed')