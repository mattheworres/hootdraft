angular.module("app").run ($rootScope, $interval, api, subscriptionKeys, draftService, messageService) ->
  #Set the cache flag for the first request
  $rootScope.loadDraftData = true
  $rootScope.draftLoadInProgress = false
  $rootScope.draftErrors = 0

  $rootScope.$on subscriptionKeys.reloadDraft, (event, args) ->
    cancelInterval = =>
      $interval.cancel $rootScope.draftIntervalPromise
      $rootScope.draftIntervalPromise = undefined

    successHandler = (draft) =>
      $rootScope.draftErrors = 0
      $rootScope.draft = draft
      $rootScope.draftLoadInProgress = false

      if $rootScope.loadDraftData
        $rootScope.loadDraftData = if draft.teams? or draft.positions? then true else false

      #Either store the cached data (first request on page load) or grab cache instead (every request afterward)
      if (draft.teams? or draft.positions?) and $rootScope.loadDraftData
        $rootScope.loadDraftData = false
        $rootScope.cachedDraftId = draft.draft_id
        $rootScope.sports = draft.sports
        $rootScope.styles = draft.styles
        $rootScope.statuses = draft.statuses
        $rootScope.teams = draft.teams
        $rootScope.positions = draft.positions
        $rootScope.depthChartPositions = draft.depthChartPositions
      else
        $rootScope.draft.sports = $rootScope.sports
        $rootScope.draft.styles = $rootScope.styles
        $rootScope.draft.statuses = $rootScope.statuses
        $rootScope.draft.teams = $rootScope.teams
        $rootScope.draft.positions = $rootScope.positions
        $rootScope.draft.depthChartPositions = $rootScope.depthChartPositions

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
      $rootScope.draftErrors++
      $rootScope.draftLoadInProgress = false
      $rootScope.showDraftMenu = false
      $rootScope.draftLoading = false

      if $rootScope.draftErrors == 2
        cancelInterval()
        $rootScope.draftError = true
        $rootScope.draftValid = false

        messageService.closeToasts()
        messageService.showError "Unable to load draft"
      else
        messageService.closeToasts()
        messageService.showWarning "We seem to be having trouble loading this draft, possibly due to network connectivity issues"

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

      #Ensure that we have teams and positions even if we don't intend to (due to latency issues)
      $rootScope.loadDraftData = args.draft_id != $rootScope.cachedDraftId 

      api.Draft.get({id: args.draft_id, get_draft_data: $rootScope.loadDraftData}, successHandler, errorHandler)

