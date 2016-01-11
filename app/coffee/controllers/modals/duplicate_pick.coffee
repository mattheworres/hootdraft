class DuplicatePickModalController extends AngularController
  @register 'DuplicatePickModalController'
  @inject '$modalInstance', 'currentPick', 'duplicateMatches'

  initialize: ->
    super()

  yesClicked: ->
    @$modalInstance.close(true)

  noClicked: ->
    @$modalInstance.close(false)

  cancel: =>
    @$modalInstance.dismiss('closed')