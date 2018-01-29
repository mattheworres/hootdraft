class FooterController {
  constructor($location) {
    this.$location = $location;
  }

  $onInit() {
    this.date = new Date();
  }

  hideFooter() {
    //TODO: Verify this still works AND if there's a better way without using $$path
    return (this.$location.$$path.indexOf('/board') !== -1) || // eslint-disable-line angular/no-private-call
      (this.$location.$$path.indexOf('/depth_chart') !== -1); // eslint-disable-line angular/no-private-call
  }
}

FooterController.$inject = [
  '$location',
];

angular.module('phpdraft.navigation').component('footerBar', {
  controller: FooterController,
  templateUrl: 'app/features/navigation/footerBar.component.html',
});
