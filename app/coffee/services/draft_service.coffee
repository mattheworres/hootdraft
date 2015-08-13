class DraftService extends AngularService
  @register 'draftService'
  @inject '$modal', '$sessionStorage'

  showPasswordModal: (draft_name) =>
    modalInstance = @$modal.open
      templateUrl: 'app/templates/modals/draft_password_modal.html',
      controller: 'DraftPasswordModalController',
      controllerAs: 'modalCtrl',
      resolve:
        draft_name: draft_name
        draft_password: @$sessionStorage.draft_password