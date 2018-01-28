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

    this.searchCommisioners = this.searchCommisioners.bind(this);
    this._getSingleCommish = this._getSingleCommish.bind(this);
    this.getCommishDrafts = this.getCommishDrafts.bind(this);
    this.setupDatatable = this.setupDatatable.bind(this);
  }

  $onInit() {
    this.drafts = [];

    this.$scope.draftTableLoading = false;

    if (this.$routeParams.commishId !== undefined) {
      this.$scope.commishId = this.$routeParams.commishId;
      this.$scope.commishSelected = true;
      this._getSingleCommish(this.$routeParams.commishId);
      this.getCommishDrafts(this.$scope.commishId);
    }
  }

  searchCommisioners(searchTerm) {
    return this.api.Commish.search({searchTerm})
      .$promise.then(data => {
        console.log('buncha stuff', this.limitToFilter, data);
        return this.limitToFilter(data.commissioners, 10);
      }).catch(() => {
        this.messageService.closeToasts();
        this.messageService.showError("Unable to search commissioners");
      });
  }

  _getSingleCommish(commishId) {
    this.api.Commish.get({commish_id: commishId})
      .$promise.then(data => {
        this.$scope.commishName = data.commissioner.name;
      }).catch(() => {
        this.messageService.showError("Unable to load commissioner");
      });
  }

  selectCommissioner(item, model, label) {
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
      return this.drafts = data;
    };

    const errorHandler = () => {
      this.$scope.draftTableLoading = false;
      this.messageService.showError("Unable to load drafts");
    };

    this.api.Draft.getDraftsByCommish({commish_id: commishId}, draftSuccessHandler, errorHandler);
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
                    masterButton: 'btn btn-primary'
                }
            }
          })
        .withColVis();

    this.dtColumnDefs = [
      this.DTColumnDefBuilder.newColumnDef(0).withOption("bSearchable", true),
      this.DTColumnDefBuilder.newColumnDef(1).withOption("bSearchable", true),
      this.DTColumnDefBuilder.newColumnDef(2).withOption("bSearchable", true),
      this.DTColumnDefBuilder.newColumnDef(3).withOption("bSearchable", true)
    ];

    return this.$scope.$on('event:dataTableLoaded', (event, loadedDT) => {
      return this.datatable = loadedDT.DataTable;
    });
  }
}
//$q, $scope, $routeParams, $location, subscriptionKeys,
//api, messageService, DTOptionsBuilder, DTColumnDefBuilder, limitToFilter
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
  'limitToFilter'
];

angular.module('phpdraft').component('byCommish', {
  controller: ByCommishController,
  templateUrl: 'app/features/home/byCommish.component.html'
})
