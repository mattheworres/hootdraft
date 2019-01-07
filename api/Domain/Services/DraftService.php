<?php
namespace PhpDraft\Domain\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Models\PhpDraftResponse;
use PhpDraft\Domain\Models\DepthChartPositionCreateModel;

class DraftService {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function GetCurrentPick($draft_id) {
    $draft_id = (int)$draft_id;

    $draft = $this->app['phpdraft.DraftRepository']->Load($draft_id);

    return (int)$draft->draft_current_pick;
  }

  public function CreateNewDraft(Draft $draft, DepthChartPositionCreateModel $depthChartModel = null) {
    $response = new PhpDraftResponse();

    try {
      $draft = $this->app['phpdraft.DraftRepository']->Create($draft);

      if ($draft->using_depth_charts) {
        $depth_charts = $this->app['phpdraft.DepthChartPositionRepository']->Save($depthChartModel, $draft->draft_id);
      }

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateDraft(Draft $draft, DepthChartPositionCreateModel $depthChartModel = null) {
    $response = new PhpDraftResponse();

    try {
      $draft = $this->app['phpdraft.DraftRepository']->Update($draft);

      if ($draft->using_depth_charts == 1) {
        $this->app['phpdraft.DepthChartPositionRepository']->DeleteAllDepthChartPositions($draft->draft_id);
        $depth_charts = $this->app['phpdraft.DepthChartPositionRepository']->Save($depthChartModel, $draft->draft_id);
      }

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateDraftStatus(Draft $draft, $old_status) {
    $response = new PhpDraftResponse();

    try {
      $draft = $this->app['phpdraft.DraftRepository']->UpdateStatus($draft);

      //If we know we're moving from undrafted to in progress, perform the necessary setup steps:
      if ($draft->draft_status != $old_status && $draft->draft_status == "in_progress") {
        //Delete all trades
        $this->app['phpdraft.TradeRepository']->DeleteAllTrades($draft->draft_id);
        //Delete all picks
        $this->app['phpdraft.PickRepository']->DeleteAllPicks($draft->draft_id);
        //Setup new picks
        $managers = $this->app['phpdraft.ManagerRepository']->GetManagersByDraftOrder($draft->draft_id);
        $descending_managers = $this->app['phpdraft.ManagerRepository']->GetManagersByDraftOrder($draft->draft_id, true);
        $this->app['phpdraft.PickRepository']->SetupPicks($draft, $managers, $descending_managers);
        //Set draft to in progress
        $this->app['phpdraft.DraftRepository']->SetDraftInProgress($draft);
      }

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function DeleteDraft(Draft $draft) {
    $response = new PhpDraftResponse();

    try {
      //Delete all trades
      $this->app['phpdraft.TradeRepository']->DeleteAllTrades($draft->draft_id);
      //Delete all depth chart positions
      $this->app['phpdraft.DepthChartPositionRepository']->DeleteAllDepthChartPositions($draft->draft_id);
      //Delete all picks
      $this->app['phpdraft.PickRepository']->DeleteAllPicks($draft->draft_id);
      //Delete all managers
      $this->app['phpdraft.ManagerRepository']->DeleteAllManagers($draft->draft_id);
      //Delete all round timers
      $this->app['phpdraft.RoundTimeRepository']->DeleteAll($draft->draft_id);
      //Delete the draft
      $this->app['phpdraft.DraftRepository']->DeleteDraft($draft->draft_id);

      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function GetDraftStats($draft_id) {
    $response = new PhpDraftResponse();

    try {
      $response->draft_statistics = $this->app['phpdraft.DraftStatsRepository']->LoadDraftStats($draft_id);
      $response->success = true;
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  public function DraftSettingUp(Draft $draft) {
    return $draft->draft_status == "undrafted";
  }

  public function DraftInProgress(Draft $draft) {
    return $draft->draft_status == "in_progress";
  }

  public function DraftComplete(Draft $draft) {
    return $draft->draft_status == "complete";
  }
}