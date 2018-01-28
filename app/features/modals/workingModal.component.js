/*
 * decaffeinate suggestions:
 * DS001: Remove Babel/TypeScript constructor workaround
 * DS102: Remove unnecessary code created because of implicit returns
 * DS206: Consider reworking classes to avoid initClass
 * Full docs: https://github.com/decaffeinate/decaffeinate/blob/master/docs/suggestions.md
 */
class WorkingModalController  {
  constructor($interval, typicalLoadingTimeMs, loadingBarMax, loadingBarIncrement) {
    this.$interval = $interval;
    this.typicalLoadingTimeMs = typicalLoadingTimeMs;
    this.loadingBarMax = loadingBarMax;
    this.loadingBarIncrement = loadingBarIncrement;
    this.initialize = this.initialize.bind(this);
  }

  static initClass() {
    this.register('WorkingModalController');
  }

  initialize() {
    if(this.typicalLoadingTimeMs !== 0) {
      return this.setupLoadingTimer();
    }
  }

  setupLoadingTimer() {
    this.progressIncrements = Math.round((this.loadingBarIncrement / this.typicalLoadingTimeMs) * 1000) / 10;

    const loadingIntervalHandler = () => {
      this.loadingBarMax += this.progressIncrements;

      if(this.loadingBarMax >= 100) {
        this.$interval.cancel(this.intervalPromise);
        return this.intervalPromise = undefined;
      }
    };

    return this.intervalPromise = this.$interval(loadingIntervalHandler, this.loadingBarIncrement);
  }
}

WorkingModalController.$inject = [
  '$interval',
  'typicalLoadingTimeMs',
  'loadingBarMax',
  'loadingBarIncrement'
]

angular.module('phpdraft').component('workingModal', {
  controller: WorkingModalController,
  templateUrl: 'app/features/modals/workingModal.component.html'
});
