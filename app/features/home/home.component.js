class HomeController {
  constructor($q, $scope, subscriptionKeys,
    api, messageService, DTOptionsBuilder, DTColumnDefBuilder) {

    this.$scope = $scope;
    this.subscriptionKeys = subscriptionKeys;
    this.api = api;
    this.messageService = messageService;
    this.DTOptionsBuilder = DTOptionsBuilder;
    this.DTColumnDefBuilder = DTColumnDefBuilder;
    this.setupDatatable = this.setupDatatable.bind(this);
  }

  $onInit() {
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

    this.api.Draft.getDraftList({}, draftSuccessHandler, errorHandler);
  }

  setupDatatable() {
    //TODO: Fix so we use fromFnPromise, and so default sorting is observed. Not working with 'Angular way'
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
      .withColVis()
      .withOption('order', [4, 'desc']);

    this.dtColumnDefs = [
      this.DTColumnDefBuilder.newColumnDef(0).withOption('bSearchable', true),
      this.DTColumnDefBuilder.newColumnDef(1).withOption('bSearchable', true),
      this.DTColumnDefBuilder.newColumnDef(2).withOption('bSearchable', true),
      this.DTColumnDefBuilder.newColumnDef(3).withOption('bSearchable', true),
      this.DTColumnDefBuilder.newColumnDef(4).withOption('bSearchable', true).withOption('sType', 'date'),
      this.DTColumnDefBuilder.newColumnDef(5).withOption('bSearchable', true),
    ];

    this.$scope.$on('event:dataTableLoaded', (event, loadedDT) => {
      this.datatable = loadedDT.DataTable;
    });
  }
}

HomeController.$inject = [
  '$q',
  '$scope',
  'subscriptionKeys',
  'api',
  'messageService',
  'DTOptionsBuilder',
  'DTColumnDefBuilder',
];

angular.module('phpdraft.home').component('home', {
  controller: HomeController,
  templateUrl: 'app/features/home/home.component.html',
});
