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
      //templateUrl: 'app/templates/modals/working_modal.html',
      //controller: 'WorkingModalController',
      //controllerAs: 'modalCtrl',
      template: '<working-modal></working-modal>',
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
    return __guardMethod__(this.modalInstance, 'close', o => o.close()); // eslint-disable-line no-use-before-define
  }
}

WorkingModalService.$inject = [
  '$uibModal',
  '$interval',
];

function __guardMethod__(obj, methodName, transform) { // eslint-disable-line no-underscore-dangle
  if (angular.isDefined(obj) && obj !== null && angular.isFunction(obj[methodName])) {
    return transform(obj, methodName);
  }

  return null;
}

angular.module('phpdraft.shared').service('workingModalService', WorkingModalService);
