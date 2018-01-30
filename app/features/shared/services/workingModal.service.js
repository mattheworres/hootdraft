class WorkingModalService {
  constructor($uibModal, $interval) {
    this.$uibModal = $uibModal;
    this.$interval = $interval;
  }

  openModal(typicalLoadingTimeMs, loadingBarIncrement) {
    const loadingTimeMs = angular.isUndefined(typicalLoadingTimeMs) ? 100 : typicalLoadingTimeMs;
    const increment = angular.isUndefined(loadingBarIncrement) ? 250 : loadingBarIncrement;
    const loadingBarMax = loadingTimeMs > 0 ? 0 : 100;

    this.closeModal();

    this.modalInstance = this.$uibModal.open({
      size: 'sm',
      keyboard: false,
      backdrop: 'static',
      template: `
      <working-modal
        typical-loading-time-ms="${loadingTimeMs}"
        loading-bar-increment="${increment}"
        loading-bar-max="${loadingBarMax}">
      </working-modal>`,
    });
  }

  closeModal() {
    if (angular.isDefined(this.modalInstance) && angular.isDefined(this.modalInstance.close)) {
      this.modalInstance.close();
    }
  }
}

WorkingModalService.$inject = [
  '$uibModal',
  '$interval',
];

angular.module('phpdraft.shared').service('workingModalService', WorkingModalService);
