class NavController/* extends BaseController */{
  constructor($rootScope, $scope, $routeParams, $location,
      messageService, subscriptionKeys, confirmActionService,
      api, errorService) {
    this.$rootScope = $rootScope;
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$location = $location;
    this.messageService = messageService;
    this.subscriptionKeys = subscriptionKeys;
    this.confirmActionService = confirmActionService;
    this.api = api;
    this.errorService = errorService;
  }

  $onInit() {
    this.draftNavHidden = true;

    //When we catch wind to collapse the menus (on xs screen sizes only), set those variables
    this.$scope.$on(this.subscriptionKeys.collapseMenus, (event, args) => {
      this.$scope.navCollapsed = true;
      this.$scope.draftNavCollapsed = true;
    });
  }

  changeDraftNav() {
    this.draftNavHidden = !this.draftNavHidden;
  }

  showDeleteDraftModal() {
    const title = "Delete the draft?";
    const message = "Are you sure you want to delete the draft? This action cannot be undone.";
    const iconClass = "fa-trash";
    const confirmButtonText = "Yes, Delete the draft";
    const deleteDraft = () => {
      const deleteSuccess = () => {
        this.messageService.showSuccess("Draft deleted");
        this.$location.path('/home');
      };

      const deleteError = () => {
        this.messageService.showError("Unable to delete draft");
      };

      return this.api.Draft.delete({draft_id: this.$routeParams.draft_id }, deleteSuccess, deleteError);
    };

    return this.confirmActionService.showConfirmationModal(message, deleteDraft, title, iconClass, confirmButtonText);
  }

  showStartDraftModal() {
    const title = "Start draft?";
    const message = "Cool, ready to start your draft? Just make sure all managers have been added and your league's details are correct - you can't change them once your draft has been started. Are you ready to get this show on the road?";
    const iconClass = "fa-play";
    const confirmButtonText = "Yep! Let's do this!";
    const startDraft = () => {
      const startSuccess = () => {
        this.messageService.showSuccess("Draft started");
        this.$rootScope.draft.setting_up = false;
        this.$rootScope.draft.in_progress = true;
        this.$rootScope.$broadcast(this.subscriptionKeys.loadDraftDependentData, { draft: this.$rootScope.draft, onPageLoad: true });
      };

      const startError = response => {
        let startErrors = '';
        if ((response.data != null ? response.data.errors : undefined) != null) {
          startErrors = this.errorService.joinErrorsForToastDisplay(response.data.errors);
        }

        this.messageService.showError(`Unable to start draft    ${startErrors}`);
      };

      return this.api.Draft.updateStatus({draft_id: this.$routeParams.draft_id, status: 'in_progress'}, startSuccess, startError);
    };

    return this.confirmActionService.showConfirmationModal(message, startDraft, title, iconClass, confirmButtonText);
  }

  showResetDraftModal() {
    const title = "Reset draft?";
    const message = "Uh oh, something wrong? No problem, we can reset your draft. Fair warning though - any and all picks or trades you've made will be deleted forever. Are you sure?";
    const iconClass = "fa-exclamation-triangle";
    const confirmButtonText = "Yes, reset my draft";
    const resetDraft = () => {
      const resetSuccess = () => {
        this.messageService.showSuccess("Draft reset");
        this.$rootScope.draft.setting_up = true;
        this.$rootScope.draft.in_progress = false;
        this.$rootScope.$broadcast(this.subscriptionKeys.loadDraftDependentData, { draft: this.$rootScope.draft, onPageLoad: true });
        this.$location.path(`/draft/${this.$routeParams.draft_id}`);
      };

      const resetError = response => {
        let restartErrors = '';
        if ((response.data != null ? response.data.errors : undefined) != null) {
          restartErrors = this.errorService.joinErrorsForToastDisplay(response.data.errors);
        }

        this.messageService.showError(`Unable to reset draft    ${restartErrors}`);
      };

      return this.api.Draft.updateStatus({draft_id: this.$routeParams.draft_id, status: 'undrafted'}, resetSuccess, resetError);
    };

    return this.confirmActionService.showConfirmationModal(message, resetDraft, title, iconClass, confirmButtonText);
  }

  _isDraftEditable() {
    if ((this.$scope.draft != null) && (this.$scope.draft.commish_editable != null)) {
      return this.$scope.draft.commish_editable;
    } else {
      return false;
    }
  }
}

NavController.$inject = [
  '$rootScope',
  '$scope',
  '$routeParams',
  '$location',
  'messageService',
  'subscriptionKeys',
  'confirmActionService',
  'api',
  'errorService'
]

angular.module('phpdraft').component('navBar', {
  controller: NavController,
  templateUrl: 'app/features/shared/components/navBar.component.html'
})
