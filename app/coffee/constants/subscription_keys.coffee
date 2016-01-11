class SubscriptionKeys extends AngularService
  @register 'subscriptionKeys'

  scopeDestroy: '$destroy'
  viewContentLoaded: '$viewContentLoaded'
  routeChangeStart: '$routeChangeStart'
  routeChangeSuccess: '$routeChangeSuccess'
  locationChangeStart: '$locationChangeStart'

  reloadDraft: 'reloadDraft'
  loadDraftDependentData: 'loadDraftDependentData'
  reloadCommishManagers: 'reloadCommishManagers'