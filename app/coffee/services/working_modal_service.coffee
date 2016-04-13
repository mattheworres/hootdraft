class WorkingModalService extends AngularService
  @register 'workingModalService'
  @inject '$uibModal', '$interval'

  openModal: (typicalLoadingTimeMs = 0, loadingBarIncrement = 250) =>
    @closeModal()

    @modalInstance = @$uibModal.open
      templateUrl: 'app/templates/modals/working_modal.html'
      controller: 'WorkingModalController',
      controllerAs: 'modalCtrl',
      size: 'sm'
      keyboard: false
      backdrop: 'static'
      resolve: 
        typicalLoadingTimeMs: =>
          typicalLoadingTimeMs
        loadingBarIncrement: =>
          loadingBarIncrement
        loadingBarMax: =>
          if(typicalLoadingTimeMs > 0)
            0
          else
            100

  closeModal: ->
    @modalInstance?.close?()

