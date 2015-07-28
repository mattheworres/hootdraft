class NavController extends BaseController
  @register 'NavController'
  @inject 'messageService'

  initialize: ->
    @draftNavHidden = false

  changeDraftNav: =>
    console.log "Flipping the prop to #{!@draftNavHidden}"
    @draftNavHidden = !@draftNavHidden
