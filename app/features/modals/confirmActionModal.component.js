class ConfirmActionModalController {
  yesClicked() {
    this.close();
  }

  noClicked() {
    this.dismiss();
  }

  cancel() {
    this.dismiss();
  }
}

angular.module('phpdraft.modals').component('phpdConfirmActionModal', {
  controller: ConfirmActionModalController,
  templateUrl: 'app/features/modals/confirmActionModal.component.html',
  bindings: {
    title: '<',
    message: '<',
    iconClass: '<',
    confirmButtonText: '<',
    close: '&',
    dismiss: '&',
  },
});
