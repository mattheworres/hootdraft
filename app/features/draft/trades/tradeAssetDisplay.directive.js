angular.module('phpdraft.draft').directive('phpdTradeAssetDisplay', () =>
  ({
    restrict: 'E',
    templateUrl: 'app/features/draft/trades/tradeAssetDisplay.directive.html',
    scope: {
      draft: '=',
      asset: '=',
    },
  })
);
