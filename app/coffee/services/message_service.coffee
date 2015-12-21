class MessageService extends AngularService
  @register 'messageService'
  @inject 'toastr'

  showSuccess: (message, title = 'Success!') ->
    @toastr.success message, title

  showError: (message, title = 'Error') ->
    @toastr.error message, title

  showInfo: (message, title = 'Information') ->
    @toastr.info message, title

  showWarning: (message, title = 'Warning') ->
    @toastr.warning message, title

  closeToasts: (toast) ->
    if typeof toast == 'undefined'
      @toastr.clear()
    else
      @toastr.clear(toast)