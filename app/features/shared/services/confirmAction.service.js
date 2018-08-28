class ConfirmActionService {
  constructor($uibModal) {
    this.$uibModal = $uibModal;
  }

  showConfirmationModal(message, confirmationCallback, userTitle, userIconClass, userConfirmButtonText) {
    const title = this.coalesceOption(userTitle, 'Are you sure?'),
      iconClass = this.coalesceOption(userIconClass, 'fa-question'),
      confirmButtonText = this.coalesceOption(userConfirmButtonText, 'Yes');

    this.modalInstance = this.$uibModal.open({
      template: `<phpd-confirm-action-modal
        title="::$resolve.title"
        message="::$resolve.message"
        icon-class="::$resolve.iconClass"
        confirm-button-text="::$resolve.confirmButtonText"
        dismiss="$dismiss()"
        close="$close()"></phpd-confirm-action-modal>`,
      controller: angular.noop,
      resolve: {
        title: () => title,
        message: () => message,
        iconClass: () => iconClass,
        confirmButtonText: () => confirmButtonText,
      },
    });

    this.modalInstance.result.then(() => {
      if (angular.isFunction(confirmationCallback)) {
        confirmationCallback();
      }
    });
  }

  coalesceOption(option, defaultOption) {
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
