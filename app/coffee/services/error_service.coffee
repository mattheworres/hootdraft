class ErrorService extends AngularService
  @register 'errorService'
  @inject '$sessionStorage'

  joinErrorsForToastDisplay: (errorsArray) ->
    errorsArray.join('      ')