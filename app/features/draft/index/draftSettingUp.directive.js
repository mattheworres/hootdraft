angular.module('phpdraft.draft').directive('phpdDraftSettingUp', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/draft/index/draftSettingUp.directive.html',
    scope: {
      draft: '=',
      status: '=',
      managers: '=',
      editableManagers: '=',
      reorderManagers: '=',
      beforeSwipe: '=',
      openAddManagers: '=',
      commishManagersLoading: '=',
      deleteManager: '=',
      enableManagerEdit: '=',
      cancelManagerEdit: '=',
      saveManager: '=',
      isEditActive: '=',
    },
  })
);
