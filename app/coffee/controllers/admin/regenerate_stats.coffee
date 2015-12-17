class RegenerateStatsController extends BaseController
  @register 'RegenerateStatsController'
  @inject '$timeout',
  'api',
  'ENV',
  'messageService',
  'workingModalService',
  'DTOptionsBuilder',
  'DTColumnDefBuilder'

  initialize: ->
    @drafts = []

    @loadAllDrafts()

  regenerateStats: (draft_id) ->
    draft_id = parseInt(draft_id, 10)
    if draft_id == 0
      @messageService.showWarning "Can't regenerate stats, invalid draft id."
      return

    @workingModalService.openModal()
    @regenerateInProgress = true

    regenerateSuccess = (response) =>
      @workingModalService.closeModal()
      @regenerateInProgress = false
      @loadAllDrafts()
      @messageService.showSuccess "Stats regenerated!"

    regenerateError = (response) =>
      console.log response
      @workingModalService.closeModal()
      @regenerateInProgress = false
      errors = response.data.error
      @messageService.showError "Unable to regenerate stats: #{error}"

    @api.Admin.regenerateDraftStats({draft_id: draft_id}, regenerateSuccess, regenerateError)

  loadAllDrafts: ->
    @workingModalService.openModal()

    draftsSuccess = (response) =>
      @workingModalService.closeModal()
      @drafts = response

    draftsError = (error) =>
      @workingModalService.closeModal()
      @messageService.showError("Unable to load drafts, check dev tools and report findings to the open source project (please!)")

    @api.Admin.getAllDrafts({}, draftsSuccess, draftsError)