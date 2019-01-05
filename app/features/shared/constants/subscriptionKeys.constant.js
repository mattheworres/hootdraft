angular.module('phpdraft.shared').constant('subscriptionKeys', {
  scopeDestroy: '$destroy',
  viewContentLoaded: '$viewContentLoaded',
  routeChangeStart: '$routeChangeStart',
  routeChangeSuccess: '$routeChangeSuccess',
  locationChangeStart: '$locationChangeStart',

  //These 3 are Coffeescript-era keys, may be deprecated
  reloadDraft: 'reloadDraft',
  loadDraftDependentData: 'loadDraftDependentData',
  reloadCommishManagers: 'reloadCommishManagers',

  draftCounterHasChanged: 'draftCounterHasChanged',
  routeHasDraft: 'routeHasDraft',
  collapseMenus: 'collapseNavMenus',
  showPasswordModal: 'showPasswordModal',

  focusPlayerAutocomplete: 'focusPlayerAutocomplete',
});
