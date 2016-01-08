angular.module("app").directive 'donationPrompt', ->
  restrict: 'E'
  templateUrl: 'app/templates/donation_prompt.html'
  controller: 'DonationPromptController'
  controllerAs: 'donationCtrl'