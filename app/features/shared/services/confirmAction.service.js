class ConfirmActionService {
  constructor($uibModal) {
    this.$uibModal = $uibModal;
  }

  showConfirmationModal(message, confirmationCallback, title, iconClass, confirmButtonText) {
    this.modalInstance = this.$uibModal.open({
      template: `<confirm-action-modal
        title="$resolve.title"
        message="$resolve.message"
        icon-class="$resolve.iconClass"
        confirm-button-text="$resolve.confirmButtonText"></confirm-action-modal>`,
      resolve: {
        title: () => {
          if ((title !== null) && (title.length > 0)) {
            return title;
          }

          return 'Are you sure?';
        },
        message: () => message,
        iconClass: () => {
          if ((iconClass !== null) && (iconClass.length > 0)) {
            return iconClass;
          }

          return 'fa-question';
        },
        confirmButtonText: () => {
          if ((confirmButtonText !== null) && (confirmButtonText.length > 0)) {
            return confirmButtonText;
          }

          return 'Yes';
        },
      },
    });

    return this.modalInstance.result.then(clickedYes => {
      this.modalInstance.dismiss('cancel');

      if (clickedYes) {
        return angular.isFunction(confirmationCallback) ? confirmationCallback() : null;
      }

      return null;
    });
  }

  closeModal() {
    if (angular.isDefined(this.modalInstance) && angular.isDefined(this.modalInstance.close)) {
      this.modalInstance.close();
    }
  }
}

ConfirmActionService.$inject = [
  '$uibModal',
];

angular.module('phpdraft.shared').service('confirmActionService', ConfirmActionService);
