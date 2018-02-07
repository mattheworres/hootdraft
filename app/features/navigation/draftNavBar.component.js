class DraftNavController {
  constructor(draftService, $rootScope, $scope, confirmActionService, messageService,
    errorService, api, $location, draftModalService, subscriptionKeys) {
    this.draftService = draftService;
    this.$rootScope = $rootScope;
    this.$scope = $scope;
    this.confirmActionService = confirmActionService;
    this.messageService = messageService;
    this.errorService = errorService;
    this.api = api;
    this.$location = $location;
    this.draftModalService = draftModalService;
    this.subscriptionKeys = subscriptionKeys;
  }

  $onInit() {
    this.showDraftMenu = false;

    //When we catch wind to collapse the menus (on xs screen sizes only), set those variables
    this.deregisterCollapseMenus = this.$rootScope.$on(this.subscriptionKeys.collapseMenus, () => {
      this.$scope.navCollapsed = true;
      this.$scope.draftNavCollapsed = true;
    });

    this.deregisterDraftUpdated = this.$rootScope.$on(this.subscriptionKeys.draftCounterHasChanged, (event, args) => {
      const {draft, status} = args;

      this.draft = draft;
      this.status = status;

      //Note: this is an approximation of the logic from Coffeescript. May need to tweak
      this.showDraftMenu = !status.error;

      this.draftNavHidden = status.error || status.badConnection || !status.valid;
    });

    this.deregisterRouteHasDraft = this.$rootScope.$on(this.subscriptionKeys.routeHasDraft, (event, args) => {
      const {hasDraft} = args;

      if (!hasDraft) {
        this.showDraftMenu = false;
      }
    });

    this.deregisterShowPasswordModal = this.$rootScope.$on(this.subscriptionKeys.showPasswordModal, () => {
      this.draftModalService.showPasswordModal(this.draft.draft_name);
    });
  }

  $onDestroy() {
    this.deregisterCollapseMenus();
    this.deregisterDraftUpdated();
    this.deregisterRouteHasDraft();
    this.deregisterShowPasswordModal();
  }

  draftNavHidden() {
    return this.status.error || this.status.badConnection || !this.status.valid;
  }

  showStartDraftModal() {
    this.draftModalService.showStartDraftModal(this.draft.draft_id);
  }

  showResetDraftModal() {
    this.draftModalService.showResetDraftModal(this.draft.draft_id);
  }

  showDeleteDraftModal() {
    this.draftModalService.showDeleteDraftModal(this.draft.draft_id);
  }
}

DraftNavController.$inject = [
  'draftService',
  '$rootScope',
  '$scope',
  'confirmActionService',
  'messageService',
  'errorService',
  'api',
  '$location',
  'draftModalService',
  'subscriptionKeys',
];

angular.module('phpdraft.navigation').component('phpdDraftNavBar', {
  controller: DraftNavController,
  templateUrl: 'app/features/navigation/draftNavBar.component.html',
});
