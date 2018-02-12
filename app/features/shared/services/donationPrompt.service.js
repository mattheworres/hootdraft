const NUMBER_OF_DAYS_TO_HIDE_PROMPT = 14;

class DonationPromptService {
  constructor($localStorage) {
    this.$localStorage = $localStorage;
  }

  shouldShowPrompt() {
    //If we have never said "hide the prompt", continue showing it
    if (angular.isUndefined(this.$localStorage.hide_donation_prompt)) {
      return true;
    }

    //Ensure we have a date to work with, otherwise uncache the hide flag.
    if (angular.isUndefined(this.$localStorage.show_donation_prompt_date)) {
      this._uncachePromptData();
      return true;
    }

    const today = new Date();
    const showDonationPromptDate = new Date(this.$localStorage.show_donation_prompt_date);

    //If we've passed the date to show the prompt again, uncache and go ahead and do so.
    if (today > showDonationPromptDate) {
      this._uncachePromptData();
      return true;
    }

    return false;
  }

  hideDonationPrompt() {
    this._uncachePromptData();

    const today = new Date();
    today.setDate(today.getDate() + NUMBER_OF_DAYS_TO_HIDE_PROMPT);

    this._cachePromptData(today);
  }

  _uncachePromptData() {
    delete this.$localStorage.hide_donation_prompt;
    delete this.$localStorage.show_donation_prompt_date;
  }

  _cachePromptData(showPromptDate) {
    this.$localStorage.hide_donation_prompt = true; // eslint-disable-line camelcase
    this.$localStorage.show_donation_prompt_date = showPromptDate; // eslint-disable-line camelcase
  }
}

DonationPromptService.$inject = [
  '$localStorage',
];

angular.module('phpdraft.shared').service('donationPromptService', DonationPromptService);
