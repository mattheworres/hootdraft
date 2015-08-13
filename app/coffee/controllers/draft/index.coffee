class DraftIndexController extends BaseController
  @register 'DraftIndexController'
  @inject '$scope',
  '$rootScope',
  'subscriptionKeys',
  'api',
  'messageService',
  'workingModalService',
  'workingModalService',
  'api'

  initialize: ->
    @deregister = @$scope.$on @subscriptionKeys.loadDraftDependentData, (event, args) ->

    @$scope.$on @subscriptionKeys.scopeDestroy, (event, args) =>
      @deregister()