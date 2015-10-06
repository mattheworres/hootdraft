angular.module("app").run ($rootScope, $interval, api, subscriptionKeys, draftService, messageService) ->
  $rootScope.$on subscriptionKeys.reloadDraft, (event, args) ->
    $rootScope.draftLoadInProgress = false

    cancelInterval = =>
      $interval.cancel $rootScope.draftIntervalPromise
      $rootScope.draftIntervalPromise = undefined

    successHandler = (draft) =>
      $rootScope.draft = draft
      $rootScope.showDraftMenu = true
      $rootScope.draftLoading = false

      if args.hasResetPassword? and args.hasResetPassword and $rootScope.draftIntervalPromise == undefined
        $rootScope.draftIntervalPromise = $interval $rootScope.draftIntervalHandler, 2750
      
      if draft.is_locked
        $rootScope.draftLocked = true
        $rootScope.draftValid = false
        draftService.showPasswordModal()
        cancelInterval()

      $rootScope.draftError = false
      $rootScope.draftValid = true
      
      $rootScope.$broadcast subscriptionKeys.loadDraftDependentData, { draft: draft, onPageLoad: args.onPageLoad }

      #If the draft is completed, a single load is all we need, so cancel the interval
      if draft.complete? and draft.complete == true and $rootScope.draftIntervalPromise != undefined
        cancelInterval()

    errorHandler = =>
      cancelInterval()
      $rootScope.draftLoading = false
      $rootScope.draftError = true
      $rootScope.draftValid = false
      $rootScope.showDraftMenu = false
      messageService.showError "Unable to load draft"

    if not args.draft_id?
      $rootScope.draftLoading = false
      $rootScope.draftError = true
      $rootScope.draftValid = false
      $rootScope.showDraftMenu = false
      messageService.showError "Unable to load draft"
      return

    if not $rootScope.draftLoading and not $rootScope.draftLoadInProgress
      $rootScope.draftLoadInProgress = true
      $rootScope.draftLoading = args.onPageLoad? and args.onPageLoad
      $rootScope.draftError = false
      $rootScope.draftLocked = false
      api.Draft.get({id: args.draft_id}, successHandler, errorHandler)