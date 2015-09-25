class FooterController extends BaseController
  @register 'FooterController'
  ###@inject '$location'###

  initialize: ->
    @date = new Date()
