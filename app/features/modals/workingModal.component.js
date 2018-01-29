class WorkingModalController {
  constructor($interval, typicalLoadingTimeMs, loadingBarMax, loadingBarIncrement) {
    this.$interval = $interval;
    this.typicalLoadingTimeMs = typicalLoadingTimeMs;
    this.loadingBarMax = loadingBarMax;
    this.loadingBarIncrement = loadingBarIncrement;
    this.initialize = this.initialize.bind(this);
  }

  $onInit() {
    if (this.typicalLoadingTimeMs !== 0) {
      this.setupLoadingTimer();
    }
  }

  setupLoadingTimer() {
    this.progressIncrements = Math.round((this.loadingBarIncrement / this.typicalLoadingTimeMs) * 1000) / 10;

    const loadingIntervalHandler = () => {
      this.loadingBarMax += this.progressIncrements;

      if (this.loadingBarMax >= 100) {
        this.$interval.cancel(this.intervalPromise);
        this.intervalPromise = undefined;// eslint-disable-line no-undefined
      }
    };

    this.intervalPromise = this.$interval(loadingIntervalHandler, this.loadingBarIncrement);
  }
}

WorkingModalController.$inject = [
  '$interval',
  'typicalLoadingTimeMs',
  'loadingBarMax',
  'loadingBarIncrement',
];

angular.module('phpdraft.modals').component('workingModal', {
  controller: WorkingModalController,
  templateUrl: 'app/features/modals/workingModal.component.html',
});
