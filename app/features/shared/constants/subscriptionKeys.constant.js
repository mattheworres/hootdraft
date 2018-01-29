angular.module('phpdraft.shared').constant('subscriptionKeys', {
  scopeDestroy: '$destroy',
  viewContentLoaded: '$viewContentLoaded',
  routeChangeStart: '$routeChangeStart',
  routeChangeSuccess: '$routeChangeSuccess',
  locationChangeStart: '$locationChangeStart',

  reloadDraft: 'reloadDraft',
  loadDraftDependentData: 'loadDraftDependentData',
  reloadCommishManagers: 'reloadCommishManagers',

  collapseMenus: 'collapseNavMenus',

  focusPlayerAutocomplete: 'focusPlayerAutocomplete',
});
