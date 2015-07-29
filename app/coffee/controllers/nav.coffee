class NavController extends BaseController
  @register 'NavController'
  @inject 'messageService'

  initialize: ->
    @draftNavHidden = true

  changeDraftNav: ->
    @draftNavHidden = !@draftNavHidden
