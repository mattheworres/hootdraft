class ProPlayerManagementController extends BaseController
  @register 'ProPlayerManagementController'
  @inject '$timeout',
  'api',
  'ENV',
  'messageService',
  'workingModalService',
  'Upload'

  initialize: ->
    @sports = []
    @progress = 100

    sportsSuccess = (response) =>
      @sports = response

    sportsError = (error) =>
      @messageService.showError("Unable to load sports data, check dev tools and report findings to the open source project (please!)")

    @api.Admin.getSports({}, sportsSuccess, sportsError)
    @csvFile = {}
    @uploadInProgress = false

  submitClicked: ->
    if not @form.$valid or not @sport?
      @messageService.showWarning "You must select a sport AND a CSV file first!"
      return

    @uploadInProgress = true
    @progress = 10

    @form.csvFile.upload = @Upload.upload(
      url: "#{@ENV.apiEndpoint}admin/proplayers"
      method: 'POST'
      objectKey: '[csvFile]'
      data: { file: @form.csvFile.$modelValue, sport: @sport }
    )

    @form.csvFile.upload.then ((response) =>
      @$timeout =>
        @form.csvFile.result = response.data
        @uploadInProgress = false
        @form.csvFile.upload = {}
        @messageService.showSuccess "#{@sport} player data updated!"
        @progress = 100
        @sport = undefined
    ), ((response) =>
      if response.status > 0
        errors = response.data.errors.join('; ')
        @messageService.showError "Unable to upload player data: #{errors}"
        @uploadInProgress = false
        @progress = 0
    ), (evt) =>
      @progress = Math.min(100, parseInt(100 * evt.loaded / evt.total))