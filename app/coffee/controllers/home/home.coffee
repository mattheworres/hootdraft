class HomeController extends BaseController
  @register 'HomeController'
  @inject '$q',
  '$scope',
  'subscriptionKeys',
  'api',
  'messageService',
  'DTOptionsBuilder',
  'DTColumnDefBuilder'

  initialize: ->
    @drafts = []

    @$scope.draftTableLoading = true

    draftSuccessHandler = (data) =>
      @$scope.draftTableLoading = false
      @drafts = data

    errorHandler = =>
      @$scope.draftTableLoading = false
      @messageService.showError "Unable to load drafts"

    draftsPromise = @api.Draft.getDraftList({}, draftSuccessHandler, errorHandler)

  setupDatatable: =>
    #TODO: Fix so we use fromFnPromise, and so default sorting is observed. Not working with "Angular way"
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
        .withOption('order', [4, 'desc'])

    @dtColumnDefs = [
      @DTColumnDefBuilder.newColumnDef(0).withOption("bSearchable", true)
      @DTColumnDefBuilder.newColumnDef(1).withOption("bSearchable", true)
      @DTColumnDefBuilder.newColumnDef(2).withOption("bSearchable", true)
      @DTColumnDefBuilder.newColumnDef(3).withOption("bSearchable", true)
      @DTColumnDefBuilder.newColumnDef(4).withOption("bSearchable", true).withOption('sType', 'date')
      @DTColumnDefBuilder.newColumnDef(5).withOption("bSearchable", true)
    ]

    @$scope.$on 'event:dataTableLoaded', (event, loadedDT) =>
      @datatable = loadedDT.DataTable