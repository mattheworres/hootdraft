angular.module('app').directive 'showErrors', ->
  restrict: 'A'
  require: '^form'
  link: (scope, el, attrs, formCtrl) ->
    # find the text box element, which has the 'name' attribute
    inputEl = el[0].querySelector('[name]')
    # convert the native text box element to an angular element
    inputNgEl = angular.element(inputEl)
    # get the name on the text box so we know the property to check
    # on the form controller
    inputName = inputNgEl.attr('name')
    # only apply the has-error class after the user leaves the text box
    inputNgEl.bind 'blur', ->
      el.toggleClass 'has-error', formCtrl[inputName].$invalid
      return
    return