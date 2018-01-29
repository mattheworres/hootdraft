class ByCommishController {
  constructor($q, $scope, $routeParams, $location, subscriptionKeys,
    api, messageService, DTOptionsBuilder, DTColumnDefBuilder, limitToFilter) {

    this.$q = $q;
    this.$scope = $scope;
    this.$routeParams = $routeParams;
    this.$location = $location;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.messageService = messageService;
    this.DTOptionsBuilder = DTOptionsBuilder;
    this.DTColumnDefBuilder = DTColumnDefBuilder;
    this.limitToFilter = limitToFilter;
  }

  $onInit() {
    this.drafts = [];

    this.$scope.draftTableLoading = false;

    if (angular.isDefined(this.$routeParams.commishId)) {
      this.$scope.commishId = this.$routeParams.commishId;
      this.$scope.commishSelected = true;
      this.getSingleCommish(this.$routeParams.commishId);
      this.getCommishDrafts(this.$scope.commishId);
    }
  }

  searchCommisioners(searchTerm) {
    return this.api.Commish.search({searchTerm}).$promise
      .then(data => this.limitToFilter(data.commissioners, 10))
      .catch(() => {
        this.messageService.closeToasts();
        this.messageService.showError('Unable to search commissioners');
      });
  }

  getSingleCommish(commishId) {
    this.api.Commish.get({commishId}).$promise
      .then(data => {
        this.$scope.commishName = data.commissioner.name;
      }).catch(() => {
        this.messageService.showError('Unable to load commissioner');
      });
  }

  selectCommissioner(item) {
    this.$scope.commishSelected = true;
    this.$scope.commishName = item.name;
    this.$scope.commishId = item.id;
    this.$location.search('commishId', item.id);

    this.$scope.nameSearch = '';

    this.getCommishDrafts(this.$scope.commishId);
  }

  getCommishDrafts(commishId) {
    this.drafts = [];

    this.$scope.draftTableLoading = true;

    const draftSuccessHandler = data => {
      this.$scope.draftTableLoading = false;
      this.drafts = data;
    };

    const errorHandler = () => {
      this.$scope.draftTableLoading = false;
      this.messageService.showError('Unable to load drafts');
    };

    this.api.Draft.getDraftsByCommish({commishId}, draftSuccessHandler, errorHandler);
  }

  setupDatatable() {
    this.dtOptions = this.DTOptionsBuilder
      .withPaginationType('simple')
      .newOptions()
      .withDisplayLength(25)
      .withBootstrap()
      .withBootstrapOptions({
        ColVis: {
          classes: {
            masterButton: 'btn btn-primary',
          },
        },
      })
      .withColVis();

    this.dtColumnDefs = [
      this.DTColumnDefBuilder.newColumnDef(0).withOption('bSearchable', true),
      this.DTColumnDefBuilder.newColumnDef(1).withOption('bSearchable', true),
      this.DTColumnDefBuilder.newColumnDef(2).withOption('bSearchable', true),
      this.DTColumnDefBuilder.newColumnDef(3).withOption('bSearchable', true),
    ];

    return this.$scope.$on('event:dataTableLoaded', (event, loadedDT) => {
      this.datatable = loadedDT.DataTable;
    });
  }
}

ByCommishController.$inject = [
  '$q',
  '$scope',
  '$routeParams',
  '$location',
  'subscriptionKeys',
  'api',
  'messageService',
  'DTOptionsBuilder',
  'DTColumnDefBuilder',
  'limitToFilter',
];

angular.module('phpdraft.home').component('byCommish', {
  controller: ByCommishController,
  templateUrl: 'app/features/home/byCommish.component.html',
});
