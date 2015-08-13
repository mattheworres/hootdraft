class HomeController extends BaseController
  @register 'HomeController'
  @inject '$q',
  '$scope',
  'subscriptionKeys',
  'api',
  'messageService',
  'workingModalService',
  'DTOptionsBuilder',
  'DTColumnDefBuilder'

  initialize: ->
    @drafts = []

    @workingModalService.openModal()

    draftSuccessHandler = (data) =>
      @workingModalService.closeModal()
      @drafts = data

    errorHandler = =>
      @workingModalService.closeModal()
      @messageService.showError "Unable to load drafts" 

    draftsPromise = @api.Draft.getDraftList({}, draftSuccessHandler, errorHandler)

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
      @DTColumnDefBuilder.newColumnDef(4).withOption("bSearchable", true)
    ]

    @$scope.$on 'event:dataTableLoaded', (event, loadedDT) =>
      @datatable = loadedDT.DataTable