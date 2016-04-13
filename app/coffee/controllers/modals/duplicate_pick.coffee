class DuplicatePickModalController extends AngularController
  @register 'DuplicatePickModalController'
  @inject '$uibModalInstance', 'currentPick', 'duplicateMatches'

  initialize: ->
    super()

  yesClicked: ->
    @$uibModalInstance.close(true)

  noClicked: ->
    @$uibModalInstance.close(false)

  cancel: =>
    @$uibModalInstance.dismiss('closed')