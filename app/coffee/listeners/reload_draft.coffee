angular.module("app").run ($rootScope, $interval, api, subscriptionKeys, draftService, messageService) ->
  $rootScope.draftRequestPending = false
  $rootScope.$on subscriptionKeys.reloadDraft, (event, args) ->

    cancelInterval = =>
      $interval.cancel $rootScope.draftIntervalPromise
      $rootScope.draftIntervalPromise = undefined

    successHandler = (draft) =>
      $rootScope.draft = draft
      $rootScope.showDraftMenu = true
      $rootScope.draftLoading = false
      $rootScope.draftRequestPending = false

      if args.hasResetPassword? and args.hasResetPassword and $rootScope.draftIntervalPromise == undefined
        $rootScope.draftIntervalPromise = $interval $rootScope.draftIntervalHandler, 3000
      
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
      $rootScope.draftRequestPending = false
      messageService.showError "Unable to load draft"

    if not args.draft_id?
      $rootScope.draftLoading = false
      $rootScope.draftError = true
      $rootScope.draftValid = false
      $rootScope.showDraftMenu = false
      messageService.showError "Unable to load draft"
      return

    if not $rootScope.draftRequestPending
      $rootScope.draftLoading = args.onPageLoad? and args.onPageLoad
      $rootScope.draftRequestPending = true
      $rootScope.draftError = false
      $rootScope.draftLocked = false
      api.Draft.get({id: args.draft_id}, successHandler, errorHandler)