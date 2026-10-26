<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\DepthChartPositionCreateModel;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;
use HootDraft\Domain\Repositories\TradeRepository;

// GetDraftStats arrives with the stats repository in 4.6. See old-api for the original.
class DraftService {
  public function __construct(
    private DraftDataRepository $draftData,
    private DraftRepository $draftRepository,
    private DepthChartPositionRepository $depthChartPositions,
    private ManagerRepository $managers,
    private RoundTimeRepository $roundTimes,
    private PickRepository $picks,
    private \PDO $pdo,
    private TradeRepository $trades,
  ) {
  }

  public function GetCurrentPick($draft_id) {
    $draft_id = (int)$draft_id;

    $draft = $this->draftRepository->Load($draft_id);

    return (int)$draft->draft_current_pick;
  }

  public function CreateNewDraft(Draft $draft, ?DepthChartPositionCreateModel $depthChartModel = null) {
    $response = new HootDraftResponse();

    try {
      $draft = $this->draftRepository->Create($draft);

      if ($draft->using_depth_charts) {
        $this->depthChartPositions->Save($depthChartModel, $draft->draft_id);
      }

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateDraft(Draft $draft, ?DepthChartPositionCreateModel $depthChartModel = null) {
    $response = new HootDraftResponse();

    try {
      $this->pdo->beginTransaction();

      $draft = $this->draftRepository->Update($draft);

      // Picks point at depth chart positions by id once the draft starts, so the positions are only rewritten while setting up
      if ($this->DraftSettingUp($draft)) {
        $this->depthChartPositions->DeleteAllDepthChartPositions($draft->draft_id);

        if ($draft->using_depth_charts == 1) {
          $this->depthChartPositions->Save($depthChartModel, $draft->draft_id);
        }
      }

      $this->pdo->commit();
      $this->draftRepository->FlushPendingCacheResets();

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      $this->rollBack();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateDraftStatus(Draft $draft, $old_status) {
    $response = new HootDraftResponse();

    try {
      // Moving to in progress rewrites the draft's trades and picks, so it happens together or not at all
      $this->pdo->beginTransaction();

      $draft = $this->draftRepository->UpdateStatus($draft);

      //If we know we're moving from undrafted to in progress, perform the necessary setup steps:
      if ($draft->draft_status != $old_status && $draft->draft_status == "in_progress") {
        //Delete all trades
        $this->trades->DeleteAllTrades($draft->draft_id);
        //Delete all picks
        $this->picks->DeleteAllPicks($draft->draft_id);
        //Setup new picks
        $managers = $this->managers->GetManagersByDraftOrder($draft->draft_id);
        $descending_managers = $this->managers->GetManagersByDraftOrder($draft->draft_id, true);
        $this->picks->SetupPicks($draft, $managers, $descending_managers);
        //Set draft to in progress
        $this->draftRepository->SetDraftInProgress($draft);
      }

      $this->pdo->commit();
      $this->draftRepository->FlushPendingCacheResets();

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      $this->rollBack();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function DeleteDraft(Draft $draft) {
    $response = new HootDraftResponse();

    try {
      $this->pdo->beginTransaction();

      $this->trades->DeleteAllTrades($draft->draft_id);
      $this->picks->DeleteAllPicks($draft->draft_id);
      $this->depthChartPositions->DeleteAllDepthChartPositions($draft->draft_id);
      $this->managers->DeleteAllManagers($draft->draft_id);
      $this->roundTimes->DeleteAll($draft->draft_id);
      $this->draftRepository->DeleteDraft($draft->draft_id);

      $this->pdo->commit();
      $this->draftRepository->FlushPendingCacheResets();

      $response->success = true;
    } catch (\Exception $e) {
      $this->rollBack();

      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function GetDraftStatusDisplay(Draft $draft) {
    $statuses = $this->draftData->GetStatuses();

    return $statuses[$draft->draft_status];
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

  private function rollBack() {
    if ($this->pdo->inTransaction()) {
      $this->pdo->rollBack();
    }

    $this->draftRepository->FlushPendingCacheResets();
  }
}
