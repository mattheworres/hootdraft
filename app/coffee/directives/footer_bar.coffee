angular.module("app").directive 'footerBar', ->
  restrict: 'E'
  templateUrl: 'app/templates/footer_bar.html'
  controller: 'FooterController'
  controllerAs: 'footerCtrl'