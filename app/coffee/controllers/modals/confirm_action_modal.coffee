class ConfirmActionModalController extends AngularController
  @register 'ConfirmActionModalController'
  @inject '$uibModalInstance', 'title', 'message', 'iconClass', 'confirmButtonText'

  initialize: ->
    super()

  yesClicked: ->
    @$uibModalInstance.close(true)

  noClicked: ->
    @$uibModalInstance.close(false)

  cancel: =>
    @$uibModalInstance.dismiss('closed')