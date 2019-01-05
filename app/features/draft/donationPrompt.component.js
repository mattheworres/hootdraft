class DonationPromptController {
  constructor(donationPromptService) {
    this.donationPromptService = donationPromptService;
  }

  $onInit() {
    this.showDonationPrompt = this.donationPromptService.shouldShowPrompt();
  }

  hideDonationPrompt() {
    this.showDonationPrompt = false;
    this.donationPromptService.hideDonationPrompt();
  }
}

DonationPromptController.$inject = [
  'donationPromptService',
];

angular.module('phpdraft.draft').component('phpdDonationPrompt', {
  controller: DonationPromptController,
  templateUrl: 'app/features/draft/donationPrompt.component.html',
});
