class ByCommishController extends BaseController
  @register 'ByCommishController'
  @inject '$q',
  '$scope',
  '$routeParams',
  '$location',
  'subscriptionKeys',
  'api',
  'messageService',
  'DTOptionsBuilder',
  'DTColumnDefBuilder',
  'limitToFilter'

  initialize: ->
    @drafts = []

    @$scope.draftTableLoading = false

    if @$routeParams.commishId != undefined
      @$scope.commishId = @$routeParams.commishId
      @$scope.commishSelected = true
      @_getSingleCommish(@$routeParams.commishId)
      @getCommishDrafts(@$scope.commishId)

  searchCommisioners: (searchTerm) =>
    @api.Commish.search({searchTerm: searchTerm}
    ).$promise.then (data) =>
      return @limitToFilter(data.commissioners, 10)
    .catch =>
      @messageService.closeToasts()
      @messageService.showError "Unable to search commissioners"

  _getSingleCommish: (commishId) =>
    @api.Commish.get({commish_id: commishId})
    .$promise.then (data) =>
      @$scope.commishName = data.commissioner.name
    .catch =>
      @messageService.showError "Unable to load commissioner"

  selectCommissioner: (item, model, label) ->
    @$scope.commishSelected = true
    @$scope.commishName = item.name
    @$scope.commishId = item.id
    @$location.search('commishId', item.id)

    @$scope.nameSearch = ''

    @getCommishDrafts(@$scope.commishId)

  getCommishDrafts: (commishId) =>
    @drafts = []

    @$scope.draftTableLoading = true

    draftSuccessHandler = (data) =>
      @$scope.draftTableLoading = false
      @drafts = data

    errorHandler = =>
      @$scope.draftTableLoading = false
      @messageService.showError "Unable to load drafts"

    @api.Draft.getDraftsByCommish({commish_id: commishId}, draftSuccessHandler, errorHandler)

  setupDatatable: =>
    @dtOptions = @DTOptionsBuilder
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
        .withColVis()

    @dtColumnDefs = [
      @DTColumnDefBuilder.newColumnDef(0).withOption("bSearchable", true)
      @DTColumnDefBuilder.newColumnDef(1).withOption("bSearchable", true)
      @DTColumnDefBuilder.newColumnDef(2).withOption("bSearchable", true)
      @DTColumnDefBuilder.newColumnDef(3).withOption("bSearchable", true)
    ]

    @$scope.$on 'event:dataTableLoaded', (event, loadedDT) =>
      @datatable = loadedDT.DataTable