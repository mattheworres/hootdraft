angular.module("app").run ($rootScope, $sessionStorage, $routeParams, $interval, subscriptionKeys) ->
  $rootScope.$on subscriptionKeys.viewContentLoaded, (event, current, previous) ->
    #@intervalPromise = @$interval(loadingIntervalHandler, @loadingBarIncrement)
    #On all non-draft pages we need to hide the draft menu
    $rootScope.draftIntervalHandler = ->
      $rootScope.$broadcast subscriptionKeys.reloadDraft, { draft_id: $routeParams.draft_id, onPageLoad: false }

    if not $routeParams.draft_id?
      $interval.cancel $rootScope.draftIntervalPromise
      $rootScope.draftIntervalPromise = undefined
      $rootScope.showDraftMenu = false
    else
      $rootScope.$broadcast subscriptionKeys.reloadDraft, { draft_id: $routeParams.draft_id, onPageLoad: true }
      if $rootScope.draftIntervalPromise == undefined
        $rootScope.draftIntervalPromise = $interval $rootScope.draftIntervalHandler, 2750