class UserModalService {
  constructor($uibModal,
    messageService) {
    this.$uibModal = $uibModal;
    this.messageService = messageService;
  }

  showInviteModal() {
    this.modalInstance = this.$uibModal.open({
      template: `<phpd-add-new-user-modal>
        dismiss="$dismiss()"
        close="$close()"></phpd-add-new-user-modal>`,
    });

    const noop = () => angular.noop();

    this.modalInstance.result.then(noop, noop);
  }

  closeModal() {
    if (angular.isDefined(this.modalInstance)) {
      this.modalInstance.close();
    }
  }
}

UserModalService.$inject = [
  '$uibModal',
  'messageService',
];

angular.module('phpdraft.shared').service('userModalService', UserModalService);
