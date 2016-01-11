class ConfirmActionModalController extends AngularController
  @register 'ConfirmActionModalController'
  @inject '$modalInstance', 'title', 'message', 'iconClass', 'confirmButtonText'

  initialize: ->
    super()

  yesClicked: ->
    @$modalInstance.close(true)

  noClicked: ->
    @$modalInstance.close(false)

  cancel: =>
    @$modalInstance.dismiss('closed')