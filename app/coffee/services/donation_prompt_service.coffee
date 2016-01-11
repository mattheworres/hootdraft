class DonationPromptService extends AngularService
  @register 'donationPromptService'
  @inject '$localStorage'

  shouldShowPrompt: ->
    #If we have never said "hide the prompt", continue showing it
    if not @$localStorage.hide_donation_prompt?
      return true

    #Ensure we have a date to work with, otherwise uncache the hide flag.
    if not @$localStorage.show_donation_prompt_date?
      @_uncachePromptData()
      return true

    today = new Date()
    show_donation_prompt_date = new Date(@$localStorage.show_donation_prompt_date)
    
    #If we've passed the date to show the prompt again, uncache and go ahead and do so.
    if today > show_donation_prompt_date
      @_uncachePromptData()
      return true
    else
      return false

  hideDonationPrompt: ->
    @_uncachePromptData()

    today = new Date()
    today.setDate(today.getDate()+14)

    @_cachePromptData(today)

  _uncachePromptData: ->
    delete @$localStorage.hide_donation_prompt
    delete @$localStorage.show_donation_prompt_date

  _cachePromptData: (show_prompt_date) ->
    @$localStorage.hide_donation_prompt = true
    @$localStorage.show_donation_prompt_date = show_prompt_date