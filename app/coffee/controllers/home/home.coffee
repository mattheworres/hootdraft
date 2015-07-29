class HomeController extends BaseController
  @register 'HomeController'
  @inject '$q',
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
        .newOptions()
        .withPaginationType('simple')
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
      @DTColumnDefBuilder.newColumnDef(0).notSortable().withOption("bSearchable", false).withClass('checkbox-col')
      @DTColumnDefBuilder.newColumnDef(1).withOption("bSearchable", true)
      @DTColumnDefBuilder.newColumnDef(2).withOption("bSearchable", true)
    ]

    @$scope.$on 'event:dataTableLoaded', (event, loadedDT) =>
      @datatable = loadedDT.DataTable