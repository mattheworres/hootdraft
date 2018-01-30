class WorkingModalController {
  constructor($interval) {
    this.$interval = $interval;
  }

  $onInit() {
    if (parseInt(this.typicalLoadingTimeMs, 10) > 0) {
      this.setupLoadingTimer();
    }
  }

  setupLoadingTimer() {
    const increment = parseInt(this.loadingBarIncrement, 10);
    const loadingTimeMs = parseInt(this.typicalLoadingTimeMs, 10);
    this.currentProgress = parseInt(this.loadingBarMax, 10);
    this.progressIncrements = parseInt(Math.round((increment / loadingTimeMs) * 1000) / 10, 10);

    const loadingIntervalHandler = () => {
      this.currentProgress += this.progressIncrements;

      if (this.currentProgress >= 100) {
        this.$interval.cancel(this.intervalPromise);
        this.intervalPromise = undefined;// eslint-disable-line no-undefined
      }
    };

    this.intervalPromise = this.$interval(loadingIntervalHandler, increment);
  }
}

WorkingModalController.$inject = [
  '$interval',
];

angular.module('phpdraft.modals').component('workingModal', {
  controller: WorkingModalController,
  bindings: {
    typicalLoadingTimeMs: '@',
    loadingBarIncrement: '@',
    loadingBarMax: '@',
  },
  templateUrl: 'app/features/modals/workingModal.component.html',
});
