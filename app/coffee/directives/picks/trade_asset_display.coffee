angular.module("app").directive 'tradeAssetDisplay', ->
    restrict: 'E'
    templateUrl: 'app/templates/directives/picks/trade_asset_display.html'
    scope:
      draft: "="
      asset: "="
