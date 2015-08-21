<?php

namespace PhpDraft\Controllers\Commish;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PhpDraft\Domain\Entities\Trade;
use PhpDraft\Domain\Entities\TradeAsset;
use PhpDraft\Domain\Entities\Manager;
use PhpDraft\Domain\Models\PhpDraftResponse;

class TradeController {
  public function GetAssets(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $manager_id = (int)$request->get('manager_id');

    try {
      $manager = $app['phpdraft.ManagerRepository']->Load($manager_id);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to load manager #$manager_id";
      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $validity = $app['phpdraft.TradeValidator']->IsManagerValidForAssetRetrieval($draft, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $assets = $app['phpdraft.TradeService']->GetManagerAssets($manager->manager_id);

    return $app->json($assets, Response::HTTP_OK);
  }

  public function Create(Application $app, Request $request) {
    $draft_id = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draft_id);

    $new_trade = new Trade();
    $new_trade->draft_id = $draft_id;
    $new_trade->manager1_id = $request->get('manager1_id');
    $new_trade->manager2_id = $request->get('manager2_id');
    $new_trade->trade_round = $request->get('trade_round');

    $assets_json = $request->get('trade_assets');

    try {
      $new_trade->manager1 = $app['phpdraft.ManagerRepository']->Load($new_trade->manager1_id);
      $new_trade->manager2 = $app['phpdraft.ManagerRepository']->Load($new_trade->manager2_id);

      foreach($assets_json as $asset_id) {
        $new_trade_asset = new TradeAsset();
        $new_trade_asset->player = $app['phpdraft.PickRepository']->Load($asset_id);
        $new_trade_asset->was_drafted = $app['phpdraft.PickService']->PickHasBeenSelected($new_trade_asset->player);

        if($new_trade_asset->player->manager_id == $new_trade->manager1_id) {
          $new_trade_asset->oldmanager_id = $new_trade->manager1_id;
          $new_trade_asset->newmanager_id = $new_trade->manager2_id;
          $new_trade_asset->oldmanager = $new_trade->manager1;
          $new_trade_asset->newmanager = $new_trade->manager2;
        } else if($new_trade_asset->player->manager_id == $new_trade->manager2_id) {
          $new_trade_asset->oldmanager_id = $new_trade->manager2_id;
          $new_trade_asset->newmanager_id = $new_trade->manager1_id;
          $new_trade_asset->oldmanager = $new_trade->manager2;
          $new_trade_asset->newmanager = $new_trade->manager1;
        } else {
          throw new \Exception("Invalid trade data.");
        }

        $new_trade->trade_assets[] = $new_trade_asset;
      }
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $message = $e->getMessage();
      $response->errors[] = "Unable to build trade: $message";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $validity = $app['phpdraft.TradeValidator']->IsTradeValid($draft, $new_trade);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.TradeService']->EnterTrade($draft, $new_trade);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }
}