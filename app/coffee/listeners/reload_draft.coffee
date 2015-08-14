angular.module("app").run ($rootScope, $interval, api, subscriptionKeys, draftService, messageService) ->
  $rootScope.$on subscriptionKeys.reloadDraft, (event, args) ->
    successHandler = (draft) =>
      $rootScope.draft = draft
      
      if draft.draft_status? and draft.draft_status.length == 0
        draftService.showPasswordModal()

      $rootScope.$broadcast subscriptionKeys.loadDraftDependentData, { draft: draft, onPageLoad: args.onPageLoad }

      #If the draft is completed, a single load is all we need, so cancel the interval
      if draft.complete? and draft.complete == true and $rootScope.draftIntervalPromise != undefined
        $interval.cancel $rootScope.draftIntervalPromise
        $rootScope.draftIntervalPromise = undefined


      $rootScope.draftLoading = false
    errorHandler = =>
      messageService.showError("Unable to load draft")
      $rootScope.draftLoading = false

    if not args.draft_id?
      messageService.showError "Unable to load draft"
      return

    if not $rootScope.draftLoading
      $rootScope.draftLoading = args.onPageLoad? and args.onPageLoad
      api.Draft.get({id: args.draft_id}, successHandler, errorHandler)