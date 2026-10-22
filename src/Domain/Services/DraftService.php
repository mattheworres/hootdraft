<?php
namespace HootDraft\Domain\Services;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;

// Only the status helpers and GetCurrentPick are ported so far. The orchestration methods (CreateNewDraft,
// UpdateDraft, UpdateStatus, DeleteDraft) arrive with the commissioner draft controller in 4.3, and
// GetDraftStats with the stats repository in 4.6. See old-api for the originals.
class DraftService {
  public function __construct(
    private DraftDataRepository $draftData,
    private DraftRepository $draftRepository,
  ) {
  }

  public function GetCurrentPick($draft_id) {
    $draft_id = (int)$draft_id;

    $draft = $this->draftRepository->Load($draft_id);

    return (int)$draft->draft_current_pick;
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
}
