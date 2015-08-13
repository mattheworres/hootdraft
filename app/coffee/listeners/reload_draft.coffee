angular.module("app").run ($rootScope, api, subscriptionKeys, draftService, messageService) ->
  $rootScope.$on subscriptionKeys.reloadDraft, (event, args) ->
    successHandler = (draft) =>
      $rootScope.draft = draft
      
      if draft.draft_status.length == 0
        draftService.showPasswordModal()

      ###
      Store the pvs status above, then compare here, if it's changed
      we need to throw another event to be handled on the page levels
      so that they reload draft data if they need to. At that point maybe
      its not a compare of the draft statuses but just to look at it
      on the page level and say "if its in progress this"
      ###
      $rootScope.$broadcast subscriptionKeys.loadDraftDependentData, { draft_id: draft.draft_id, onPageLoad: args.onPageLoad }
      $rootScope.draftLoading = false
    errorHandler = =>
      messageService.showError("Unable to load draft")
      $rootScope.draftLoading = false

    if not args.draft_id?
      messageService.showError "Unable to load draft"
      return;

    if not $rootScope.draftLoading
      $rootScope.draftLoading = args.onPageLoad? and args.onPageLoad
      api.Draft.get({id: args.draft_id}, successHandler, errorHandler)