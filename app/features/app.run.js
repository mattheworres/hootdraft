angular.module('phpdraft').run((
  $rootScope, subscriptionKeys, $templateCache,
  $routeParams, authenticationService, messageService,
  $location, $sessionStorage) => {
  $rootScope.$on(subscriptionKeys.viewContentLoaded, () => { //eslint-disable-line angular/on-watch
    if (angular.isDefined($routeParams.draft_id) && $routeParams.draft_id !== null) {
      // $interval.cancel($rootScope.draftIntervalPromise);
      // $rootScope.draftIntervalPromise = undefined;
      // $rootScope.showDraftMenu = false;
      // $rootScope.loadDraftData = false;

      $rootScope.$broadcast(subscriptionKeys.routeHasDraft, {hasDraft: true});
    } else {
      $rootScope.$broadcast(subscriptionKeys.routeHasDraft, {hasDraft: false});
      // $rootScope.loadDraftData = true;
      // $rootScope.$broadcast(subscriptionKeys.reloadDraft, {draft_id: $routeParams.draft_id, onPageLoad: true});
    }
  });

  //TODO: may need to refactor using the private Angular
  $rootScope.$on(subscriptionKeys.routeChangeSuccess, () => { //eslint-disable-line angular/on-watch
    if (authenticationService.isAuthenticationExpired()) {
      messageService.showInfo('Your login has expired. You must log back in if you wish to continue.');
      if (($location.$$path !== null) && ($location.$$path !== '/login')) { //eslint-disable-line angular/no-private-call
        $location.path('/login');
      }
    }

    if ($location.$$path !== null) { //eslint-disable-line angular/no-private-call
      //Issue with values being reset here... Find a better way to default previousRoutes?
      /*$sessionStorage.$default(
        previousRoutes: []
      )*/
      if (angular.isUndefined($sessionStorage.previousRoutes)) {
        $sessionStorage.previousRoutes = [];
      }

      if ($sessionStorage.previousRoutes[$sessionStorage.previousRoutes.length - 1] !== $location.$$path) { //eslint-disable-line angular/no-private-call
        $sessionStorage.previousRoutes.push($location.$$path); //eslint-disable-line angular/no-private-call
      }
    }

    //In the event we're on an XS screen size, tell the menus to auto collapse (see draftNavBar)
    return $rootScope.$broadcast(subscriptionKeys.collapseMenus);
  });

  //Override the default Smart-Table pagination template for First,Prev,Next,Last buttons
  $templateCache.put('template/smart-table/pagination.html',
    '<div class="pagination" ng-if="pages.length >=2"><ul class="pagination"><li class="first" ng-if="pages.length > 7"><a ng-click="selectPage(1)" aria-label="First"><span aria-hidden="true">First</span></a></li><li class="previous"><a ng-click="selectPage(currentPage-1)" aria-label="Previous"><span aria-hidden="true">&larr;</span></a></li><li ng-repeat="page in pages" ng-class="{active: page==currentPage}"><a ng-click="selectPage(page)">{{page}}</a></li><li class="next"><a ng-click="selectPage(currentPage+1)" aria-label="Next"><span aria-hidden="true">&rarr;</span></a></li><li class="last" ng-if="pages.length > 7"><a href="" ng-click="selectPage(numPages)" aria-label="Last"><span aria-hidden="true">Last</span></a></li></ul></div>');
});
