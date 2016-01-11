angular.module("app").directive 'navBar', ->
  restrict: 'E'
  templateUrl: 'app/templates/nav_bar.html'
  controller: 'NavController'
  controllerAs: 'navCtrl'