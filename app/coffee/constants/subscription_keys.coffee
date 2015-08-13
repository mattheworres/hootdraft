class SubscriptionKeys extends AngularService
  @register 'subscriptionKeys'

  scopeDestroy: '$destroy'
  viewContentLoaded: '$viewContentLoaded'
  routeChangeSuccess: '$routeChangeSuccess'

  reloadDraft: 'reloadDraft'
  loadDraftDependentData: 'loadDraftDependentData'