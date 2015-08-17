angular.module("app").directive 'draftSettingUp', ->

    restrict: 'E'
    templateUrl: 'app/templates/directives/draft/draft_setting_up.html'
    scope:
      draft: "="
      draftValid: "="
      managers: "="
