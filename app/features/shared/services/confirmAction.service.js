class ConfirmActionService {
  constructor($uibModal) {
    this.$uibModal = $uibModal;
  }

  showConfirmationModal(message, confirmationCallback, title, iconClass, confirmButtonText) {
    this.modalInstance = this.$uibModal.open({
      template: '<confirm-action-modal></confirm-action-modal>',
      // templateUrl: 'app/templates/modals/confirm_action_modal.html',
      // controller: 'ConfirmActionModalController',
      // controllerAs: 'modalCtrl',
      resolve: {
        title: () => {
          if ((title != null) && (title.length > 0)) {
            return title;
          } else {
            return 'Are you sure?';
          }
        },
        message: () => {
          return message;
        },
        iconClass: () => {
          if ((iconClass != null) && (iconClass.length > 0)) {
            return iconClass;
          } else {
            return 'fa-question';
          }
        },
        confirmButtonText: () => {
          if ((confirmButtonText != null) && (confirmButtonText.length > 0)) {
            return confirmButtonText;
          } else {
            return 'Yes';
          }
        }
      }
    });

    return this.modalInstance.result.then(clickedYes => {
        this.modalInstance.dismiss('cancel');

        if (clickedYes) {
          return (typeof confirmationCallback === 'function' ? confirmationCallback() : undefined);
        }
    });
  }

  closeModal() {
    return __guardMethod__(this.modalInstance, 'close', o => o.close());
  }
}

function __guardMethod__(obj, methodName, transform) {
  if (typeof obj !== 'undefined' && obj !== null && typeof obj[methodName] === 'function') {
    return transform(obj, methodName);
  } else {
    return undefined;
  }
}

ConfirmActionService.$inject = [
  '$uibModal'
];

angular.module('phpdraft').service('confirmActionService', ConfirmActionService);
