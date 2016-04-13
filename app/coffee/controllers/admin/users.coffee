class UsersController extends BaseController
  @register 'UsersController'
  @inject '$scope',
  '$uibModal',
  'api',
  'messageService',
  'confirmActionService',
  'workingModalService'

  initialize: ->
    @users = []
    @roles = []

    userInitSuccess = (response) =>
      @users = response.users
      @roles = response.roles

    userInitError = (error) =>
      @messageService.showError "Unable to load users"

    @users = @api.User.getAll({}, userInitSuccess, userInitError)

  editUser: (user, userIndex) ->
    @editedUser = user
    @editedUserIndex = userIndex

    #Callback fcn that we pass to the modal, so we can keep the list up to date
    updateUsersCollection = (updatedUser) =>
      @users[userIndex] = updatedUser
      @messageService.showSuccess "#{@editedUser.name} updated"

    @$uibModal.open
      templateUrl: 'app/templates/modals/admin_edit_user_modal.html',
      controller: 'AdminEditUserController',
      controllerAs: 'modalCtrl',
      resolve:
        user: =>
          return user
        userUpdateCallback: =>
          return updateUsersCollection

  deleteUser: (user, itemIndex) ->
    title = "Delete #{user.name}?"
    message = "Are you sure you want to delete #{user.name}? This action cannot be undone."
    iconClass = "fa-trash"
    confirmButtonText = "Yes, Delete User"

    deleteUserHandler = =>
      deleteSuccess = =>
        @workingModalService.closeModal()
        @users.splice itemIndex, 1
        @messageService.showSuccess "User deleted"

      deleteError = =>
        @workingModalService.closeModal()
        @messageService.showError "Unable to delete user"

      @workingModalService.openModal()
      @api.User.delete({id: user.id}, deleteSuccess, deleteError)

    @confirmActionService.showConfirmationModal(message, deleteUserHandler, title, iconClass, confirmButtonText)





