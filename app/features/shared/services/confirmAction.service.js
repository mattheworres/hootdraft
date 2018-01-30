class ConfirmActionService {
  constructor($uibModal) {
    this.$uibModal = $uibModal;
  }

  showConfirmationModal(message, confirmationCallback, userTitle, userIconClass, userConfirmButtonText) {
    const title = this.coerceOption(userTitle, 'Are you sure?'),
      iconClass = this.coerceOption(userIconClass, 'fa-question'),
      confirmButtonText = this.coerceOption(userConfirmButtonText, 'Yes');

    this.modalInstance = this.$uibModal.open({
      template: `<confirm-action-modal
        title="${title}"
        message="${message}"
        icon-class="${iconClass}"
        confirm-button-text="${confirmButtonText}"></confirm-action-modal>`,
    });

    this.modalInstance.result.then(clickedYes => {
      this.modalInstance.dismiss('cancel');

      if (clickedYes && angular.isFunction(confirmationCallback)) {
        confirmationCallback();
      }
    });
  }

  coerceOption(option, defaultOption) {
    return option !== null && option.length > 0 ? option : defaultOption;
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
