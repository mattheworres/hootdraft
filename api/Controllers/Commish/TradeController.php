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
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $managerId = (int)$request->get('manager_id');

    try {
      $manager = $app['phpdraft.ManagerRepository']->Load($managerId);
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $response->errors[] = "Unable to load manager #$managerId";
      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $validity = $app['phpdraft.TradeValidator']->IsManagerValidForAssetRetrieval($draft, $manager);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $assets = $app['phpdraft.TradeService']->GetManagerAssets($draft, $manager->manager_id);

    return $app->json($assets, Response::HTTP_OK);
  }

  public function Create(Application $app, Request $request) {
    $draftId = (int)$request->get('draft_id');
    $draft = $app['phpdraft.DraftRepository']->Load($draftId);

    $newTrade = new Trade();
    $newTrade->draft_id = $draftId;
    $newTrade->manager1_id = $request->get('manager1_id');
    $newTrade->manager2_id = $request->get('manager2_id');

    $newTrade->trade_round = $draft->draft_current_round;

    $assets_json = $request->get('trade_assets');

    try {
      $newTrade->manager1 = $app['phpdraft.ManagerRepository']->Load($newTrade->manager1_id);
      $newTrade->manager2 = $app['phpdraft.ManagerRepository']->Load($newTrade->manager2_id);

      foreach($assets_json as $asset_id) {
        $newTradeAsset = new TradeAsset();
        $newTradeAsset->player = $app['phpdraft.PickRepository']->Load($asset_id);
        $newTradeAsset->was_drafted = $app['phpdraft.PickService']->PickHasBeenSelected($newTradeAsset->player);

        if($newTradeAsset->player->manager_id == $newTrade->manager1_id) {
          $newTradeAsset->oldmanager_id = $newTrade->manager1_id;
          $newTradeAsset->newmanager_id = $newTrade->manager2_id;
          $newTradeAsset->oldmanager = $newTrade->manager1;
          $newTradeAsset->newmanager = $newTrade->manager2;
        } else if($newTradeAsset->player->manager_id == $newTrade->manager2_id) {
          $newTradeAsset->oldmanager_id = $newTrade->manager2_id;
          $newTradeAsset->newmanager_id = $newTrade->manager1_id;
          $newTradeAsset->oldmanager = $newTrade->manager2;
          $newTradeAsset->newmanager = $newTrade->manager1;
        } else {
          throw new \Exception("Invalid trade data.");
        }

        $newTrade->trade_assets[] = $newTradeAsset;
      }
    } catch(\Exception $e) {
      $response = new PhpDraftResponse(false, array());
      $message = $e->getMessage();
      $response->errors[] = "Unable to build trade: $message";

      return $app->json($response, Response::HTTP_BAD_REQUEST);
    }

    $validity = $app['phpdraft.TradeValidator']->IsTradeValid($draft, $newTrade);

    if(!$validity->success) {
      return $app->json($validity, Response::HTTP_BAD_REQUEST);
    }

    $response = $app['phpdraft.TradeService']->EnterTrade($draft, $newTrade);

    return $app->json($response, $response->responseType(Response::HTTP_CREATED));
  }
}