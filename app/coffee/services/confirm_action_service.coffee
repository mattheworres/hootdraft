class ConfirmActionService extends AngularService
  @register 'confirmActionService'
  @inject '$modal', '$sessionStorage'

  showConfirmationModal: (message, confirmationCallback, title, iconClass, confirmButtonText) ->
    @modalInstance = @$modal.open
      templateUrl: 'app/templates/modals/confirm_action_modal.html',
      controller: 'ConfirmActionModalController',
      controllerAs: 'modalCtrl',
      resolve:
        title: =>
          if title? and title.length > 0
            return title
          else 
            return 'Are you sure?'
        message: =>
          message
        iconClass: =>
          if iconClass? and iconClass.length > 0
            return iconClass
          else 
            return 'fa-question'
        confirmButtonText: =>
          if confirmButtonText? and confirmButtonText.length > 0
            return confirmButtonText
          else 
            return 'Yes'

    @modalInstance.result.then (clickedYes) =>
        @modalInstance.dismiss('cancel')

        if clickedYes
          confirmationCallback?()

  closeModal: ->
    @modalInstance?.close?()