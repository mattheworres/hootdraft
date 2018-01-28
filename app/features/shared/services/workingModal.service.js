class WorkingModalService {
  constructor($uibModal, $interval) {
    this.openModal = this.openModal.bind(this);
    this.$uibModal = $uibModal;
    this.$interval = $interval;
  }

  openModal(typicalLoadingTimeMs, loadingBarIncrement) {
    if (typicalLoadingTimeMs == null) { typicalLoadingTimeMs = 0; }
    if (loadingBarIncrement == null) { loadingBarIncrement = 250; }
    this.closeModal();

    return this.modalInstance = this.$uibModal.open({
      //templateUrl: 'app/templates/modals/working_modal.html',
      //controller: 'WorkingModalController',
      //controllerAs: 'modalCtrl',
      template: '<working-modal></working-modal>',
      size: 'sm',
      keyboard: false,
      backdrop: 'static',
      resolve: {
        typicalLoadingTimeMs: () => {
          return typicalLoadingTimeMs;
        },
        loadingBarIncrement: () => {
          return loadingBarIncrement;
        },
        loadingBarMax: () => {
          if(typicalLoadingTimeMs > 0) {
            return 0;
          } else {
            return 100;
          }
        }
      }
    });
  }

  closeModal() {
    return __guardMethod__(this.modalInstance, 'close', o => o.close());
  }
}

WorkingModalService.$inject = [
  '$uibModal',
  '$interval'
];

function __guardMethod__(obj, methodName, transform) {
  if (typeof obj !== 'undefined' && obj !== null && typeof obj[methodName] === 'function') {
    return transform(obj, methodName);
  } else {
    return undefined;
  }
}
