class FooterController {
  constructor($location) {
    this.$location = $location;
  }

  $onInit() {
    this.date = new Date();
  }

  hideFooter() {
    return (this.$location.$$path.indexOf('/board') !== -1) ||
      (this.$location.$$path.indexOf('/depth_chart') !== -1);
  }
}

FooterController.$inject = [
  '$location'
];

angular.module('phpdraft').component('footerBar', {
  controller: FooterController,
  templateUrl: 'app/features/shared/components/footerBar.component.html'
})
