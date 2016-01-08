class DonationPromptController extends BaseController
  @register 'DonationPromptController'
  @inject 'donationPromptService'

  initialize: ->
    @showDonationPrompt = @donationPromptService.shouldShowPrompt()

  hideDonationPrompt: ->
    @showDonationPrompt = false
    @donationPromptService.hideDonationPrompt()