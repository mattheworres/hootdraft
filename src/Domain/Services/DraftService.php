<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\DepthChartPositionCreateModel;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DepthChartPositionRepository;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\DraftStatsRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Repositories\PickRepository;
use HootDraft\Domain\Repositories\RoundTimeRepository;
use HootDraft\Domain\Repositories\TradeRepository;
use Psr\Log\LoggerInterface;

class DraftService {
  public function __construct(
    private DraftDataRepository $draftData,
    private DraftRepository $draftRepository,
    private DepthChartPositionRepository $depthChartPositions,
    private ManagerRepository $managers,
    private RoundTimeRepository $roundTimes,
    private PickRepository $picks,
    private TransactionService $transactions,
    private TradeRepository $trades,
    private DraftStatsRepository $stats,
    private LoggerInterface $logger,
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
      // The draft row and its depth chart positions are saved together or not at all
      $draft = $this->transactions->Run(function () use ($draft, $depthChartModel) {
        $draft = $this->draftRepository->Create($draft);

        if ($draft->UsesDepthCharts()) {
          $this->depthChartPositions->Save($depthChartModel, $draft->draft_id);
        }

        return $draft;
      });

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      // The raw message can hold SQL, so it goes to the log and the commissioner gets a plain one
      $this->logger->error("Unable to create the draft: " . $e->getMessage(), ['exception' => $e]);
      $response->success = false;
      $response->errors = array("The draft could not be created. Nothing was saved, please try again.");
    }

    return $response;
  }

  public function UpdateDraft(Draft $draft, ?DepthChartPositionCreateModel $depthChartModel = null) {
    $response = new HootDraftResponse();

    try {
      $draft = $this->transactions->Run(function () use ($draft, $depthChartModel) {
        $draft = $this->draftRepository->Update($draft);

        // Picks point at depth chart positions by id once the draft starts, so the positions are only rewritten while setting up
        if ($this->DraftSettingUp($draft)) {
          $this->depthChartPositions->DeleteAllDepthChartPositions($draft->draft_id);

          if ($draft->UsesDepthCharts()) {
            $this->depthChartPositions->Save($depthChartModel, $draft->draft_id);
          }
        }

        return $draft;
      });

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function UpdateDraftStatus(Draft $draft, $old_status) {
    $response = new HootDraftResponse();

    try {
      // Moving to in progress rewrites the draft's trades and picks, so it happens together or not at all
      $draft = $this->transactions->Run(function () use ($draft, $old_status) {
        $draft = $this->draftRepository->UpdateStatus($draft);

        //If we know we're moving from undrafted to in progress, perform the necessary setup steps:
        if ($draft->draft_status != $old_status && $draft->draft_status == Draft::STATUS_IN_PROGRESS) {
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

        return $draft;
      });

      $response->success = true;
      $response->draft = $draft;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  /**
   * Joins the caller's transaction when one is open (deleting a user deletes all their drafts as one unit); the
   * caller then owns the commit, the rollback and the cache flush.
   */
  public function DeleteDraft(Draft $draft) {
    $response = new HootDraftResponse();

    try {
      $this->transactions->Run(function () use ($draft) {
        $this->trades->DeleteAllTrades($draft->draft_id);
        $this->picks->DeleteAllPicks($draft->draft_id);
        $this->depthChartPositions->DeleteAllDepthChartPositions($draft->draft_id);
        $this->managers->DeleteAllManagers($draft->draft_id);
        $this->roundTimes->DeleteAll($draft->draft_id);
        $this->stats->DeleteStats($draft->draft_id);
        $this->draftRepository->DeleteDraft($draft->draft_id);
      });

      $response->success = true;
    } catch (\Exception $e) {
      $response->success = false;
      $response->errors = array($e->getMessage());
    }

    return $response;
  }

  public function GetDraftStats($draft_id) {
    $response = new HootDraftResponse();

    try {
      $response->draft_statistics = $this->stats->LoadDraftStats($draft_id);
      $response->success = true;
    } catch (\Exception $e) {
      $message = $e->getMessage();
      $response->success = false;
      $response->errors[] = $message;
    }

    return $response;
  }

  public function GetDraftStatusDisplay(Draft $draft) {
    $statuses = $this->draftData->GetStatuses();

    return $statuses[$draft->draft_status];
  }

  public function DraftSettingUp(Draft $draft) {
    return $draft->draft_status == Draft::STATUS_SETTING_UP;
  }

  public function DraftInProgress(Draft $draft) {
    return $draft->draft_status == Draft::STATUS_IN_PROGRESS;
  }

  public function DraftComplete(Draft $draft) {
    return $draft->draft_status == Draft::STATUS_COMPLETE;
  }
}
