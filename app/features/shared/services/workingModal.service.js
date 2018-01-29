class WorkingModalService {
  constructor($uibModal, $interval) {
    this.openModal = this.openModal.bind(this);
    this.$uibModal = $uibModal;
    this.$interval = $interval;
  }

  openModal(typicalLoadingTimeMs, loadingBarIncrement) {
    const loadingTimeMs = typicalLoadingTimeMs === null ? 0 : typicalLoadingTimeMs;
    const increment = loadingBarIncrement === null ? 250 : loadingBarIncrement;

    this.closeModal();

    this.modalInstance = this.$uibModal.open({
      template: '<working-modal typical-loading-time-ms="$resolve.typicalLoadingTimeMs" loading-bar-increment="$resolve.loadingBarIncrement" loading-bar-max="$resolve.loadingBarMax"></working-modal>',
      size: 'sm',
      keyboard: false,
      backdrop: 'static',
      resolve: {
        typicalLoadingTimeMs: () => loadingTimeMs,
        loadingBarIncrement: () => increment,
        loadingBarMax: () => {
          if (loadingTimeMs > 0) {
            return 0;
          }

          return 100;
        },
      },
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
