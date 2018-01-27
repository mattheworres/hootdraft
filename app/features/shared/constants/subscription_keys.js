class SubscriptionKeys extends AngularService {
	constructor() {
		this.register('subscriptionKeys');

		this.scopeDestroy = '$destroy';
		this.viewContentLoaded = '$viewContentLoaded';
		this.routeChangeStart = '$routeChangeStart';
		this.routeChangeSuccess = '$routeChangeSuccess';
		this.locationChangeStart = '$locationChangeStart';

		this.reloadDraft = 'reloadDraft';
		this.loadDraftDependentData = 'loadDraftDependentData';
		this.reloadCommishManagers = 'reloadCommishManagers';

		this.collapseMenus = 'collapseNavMenus';

		this.focusPlayerAutocomplete = 'focusPlayerAutocomplete';
	}
}
