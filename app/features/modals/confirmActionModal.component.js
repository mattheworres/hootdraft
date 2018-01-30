class ConfirmActionModalController {
  constructor($uibModalInstance) {
    this.$uibModalInstance = $uibModalInstance;
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
];

angular.module('phpdraft.modals').component('confirmActionModal', {
  controller: ConfirmActionModalController,
  templateUrl: 'app/features/modals/confirmActionModal.component.html',
  bindings: {
    title: '@',
    message: '@',
    iconClass: '@',
    confirmButtonText: '@',
  },
});
