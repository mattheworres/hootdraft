class ConfirmActionModalController {
  constructor($uibModalInstance, title, message, iconClass, confirmButtonText) {
    this.$uibModalInstance = $uibModalInstance;
    this.title = title;
    this.message = message;
    this.iconClass = iconClass;
    this.confirmButtonText = confirmButtonText;

    this.inject();
    this.cancel = this.cancel.bind(this);
  }

  yesClicked() {
    this.$uibModalInstance.close(true);
  }

  noClicked() {
    this.$uibModalInstance.close(false);
  }

  cancel() {
    this.$uibModalInstance.dismiss('closed');
  }
}

ConfirmActionModalController.$inject = [
  '$uibModalInstance',
  'title',
  'message',
  'iconClass',
  'confirmButtonText'
];

angular.module('phpdraft').component('confirmActionModal', {
  controller: ConfirmActionModalController,
  templateUrl: 'app/features/modals/confirmActionModal.component.html'
})
