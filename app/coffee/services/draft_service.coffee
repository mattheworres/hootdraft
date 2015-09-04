class DraftService extends AngularService
  @register 'draftService'
  @inject '$modal', '$sessionStorage'

  showPasswordModal: (draft_name) =>
    @modalInstance = @$modal.open
      templateUrl: 'app/templates/modals/draft_password_modal.html',
      controller: 'DraftPasswordModalController',
      controllerAs: 'modalCtrl',
      resolve:
        draft_name: draft_name
        draft_password: @$sessionStorage.draft_password

  showAddManagersModal: (draft_id) =>
    @modalInstance = @$modal.open
      templateUrl: 'app/templates/modals/add_managers.html',
      controller: 'AddManagersController',
      controllerAs: 'addManagersCtrl',
      resolve:
        draft_id: =>
          draft_id

  closeModal: ->
    @modalInstance?.close?()