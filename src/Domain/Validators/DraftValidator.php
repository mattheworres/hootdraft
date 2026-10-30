<?php
namespace HootDraft\Domain\Validators;

use HootDraft\Domain\Entities\Draft;
use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftDataRepository;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Repositories\ManagerRepository;
use HootDraft\Domain\Services\DraftService;
use HootDraft\Security\CurrentUser;
use HootDraft\Security\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

class DraftValidator {
  public function __construct(
    private DraftRepository $draftRepository,
    private DraftDataRepository $draftData,
    private DraftService $draftService,
    private ManagerRepository $managers,
    private CurrentUser $currentUser,
    #[Autowire('%hootdraft.draft_password_header%')] private string $draftPasswordHeader,
  ) {
  }

  public function IsDraftViewableForUser($draft_id, Request $request) {
    $draft = $this->draftRepository->Load($draft_id);
    $current_user = $this->currentUser->get();
    $draft_password = $request->headers->get($this->draftPasswordHeader, '');

    if ($current_user != null && ($draft->commish_id == $current_user->getId() || $current_user->isAdmin())) {
      return true;
    }

    if (empty($draft->draft_password) || hash_equals((string) $draft->draft_password, (string) $draft_password)) {
      return true;
    }

    return false;
  }

  public function IsDraftEditableForUser(Draft $draft, ?User $current_user) {
    if (!empty($current_user) && $draft->commish_id == $current_user->getId()) {
      return true;
    }

    if (!empty($current_user) && $current_user->isAdmin()) {
      return true;
    }

    return false;
  }

  public function IsDraftValidForCreateAndUpdate(Draft $draft) {
    $valid = true;
    $errors = array();
    $draft_sports = $this->draftData->GetSports();
    $draft_styles = $this->draftData->GetStyles();

    if (empty($draft->commish_id)
      || empty($draft->draft_name)
      || empty($draft->draft_sport)
      || empty($draft->draft_style)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if (!empty($draft->draft_password) && strlen($draft->draft_password) > 255) {
      $errors[] = "Password is above maximum length.";
      $valid = false;
    }

    if (strlen((string)$draft->draft_name) > 255) {
      $errors[] = "Draft name is above maximum length";
      $valid = false;
    }

    if (strlen((string)$draft->draft_sport) < 3 || strlen((string)$draft->draft_sport) > 4 || strlen($draft_sports[$draft->draft_sport] ?? '') == 0) {
      $errors[] = "Draft sport is an invalid value.";
      $valid = false;
    }

    if (!array_key_exists((string)$draft->draft_style, $draft_styles)) {

      $errors[] = "Draft style is an invalid value.";
      $valid = false;
    }

    if (!$this->draftRepository->NameIsUnique($draft->draft_name, $draft->draft_id)) {
      $errors[] = "Draft name is already taken, please choose a different name.";
      $valid = false;
    }

    if ($draft->draft_rounds < 1 || $draft->draft_rounds > 30) {
      $errors[] = "Invalid number of draft rounds.";
      $valid = false;
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsDraftStatusValid(Draft $draft, $old_status) {
    $valid = true;
    $errors = array();
    $draft_statuses = $this->draftData->GetStatuses();

    if ($old_status == "complete") {
      $valid = false;
      $errors[] = "The draft is completed, therefore its status cannot be changed.";
    }

    if ($draft->draft_status == "complete") {
      $valid = false;
      $errors[] = "You cannot set the draft as completed manually.";
    }

    if (empty($draft->draft_status)) {
      $errors[] = "One or more missing fields.";
      $valid = false;
    }

    if (!array_key_exists((string) $draft->draft_status, $draft_statuses)) {
      $errors[] = "Draft status is an invalid value.";
      $valid = false;
    }

    if ($draft->draft_status == "in_progress" && !$this->managers->DraftHasManagers($draft->draft_id)) {
      $valid = false;
      $errors[] = "A draft must have at least 2 managers before it can begin.";
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsDraftSettingUp(Draft $draft) {
    $valid = true;
    $errors = array();
    $draft_statuses = $this->draftData->GetStatuses();
    $current_status_text = strtolower($draft_statuses[$draft->draft_status] ?? '');
    $is_setting_up = $this->draftService->DraftSettingUp($draft);

    if (!$is_setting_up) {
      $valid = false;
      $errors[] = "Unable to work on draft #$draft->draft_id: draft is $current_status_text";
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsDraftSettingUpOrInProgress(Draft $draft) {
    $valid = true;
    $errors = array();
    $draft_statuses = $this->draftData->GetStatuses();
    $current_status_text = strtolower($draft_statuses[$draft->draft_status] ?? '');
    $is_setting_up = $this->draftService->DraftSettingUp($draft);
    $is_in_progress = $this->draftService->DraftInProgress($draft);

    if (!$is_setting_up && !$is_in_progress) {
      $valid = false;
      $errors[] = "Unable to work on draft #$draft->draft_id: draft is $current_status_text";
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsDraftInProgress(Draft $draft) {
    $valid = true;
    $errors = array();
    $draft_statuses = $this->draftData->GetStatuses();
    $current_status_text = strtolower($draft_statuses[$draft->draft_status] ?? '');
    $is_in_progress = $this->draftService->DraftInProgress($draft);

    if (!$is_in_progress) {
      $valid = false;
      $errors[] = "Unable to work on draft #$draft->draft_id: draft is $current_status_text";
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsDraftInProgressOrComplete(Draft $draft) {
    $valid = true;
    $errors = array();
    $draft_statuses = $this->draftData->GetStatuses();
    $current_status_text = strtolower($draft_statuses[$draft->draft_status] ?? '');
    $is_in_progress = $this->draftService->DraftInProgress($draft);
    $is_complete = $this->draftService->DraftComplete($draft);

    if (!$is_complete && !$is_in_progress) {
      $valid = false;
      $errors[] = "Unable to work on draft #$draft->draft_id: draft is $current_status_text";
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsDraftInProgressOrCompletedInLessThanTen(Draft $draft) {
    $valid = true;
    $errors = array();
    $draft_statuses = $this->draftData->GetStatuses();
    $current_status_text = strtolower($draft_statuses[$draft->draft_status] ?? '');
    $is_in_progress = $this->draftService->DraftInProgress($draft);
    $is_complete = $this->draftService->DraftComplete($draft);
    $isLessThanTenMinutes = false;

    if ($is_complete) {
      $utc_timezone = new \DateTimeZone("UTC");
      $now_utc = new \DateTime('now', $utc_timezone);
      $end_time = new \DateTime($draft->draft_end_time, $utc_timezone);
      $now_seconds = $now_utc->getTimestamp();
      $end_seconds = $end_time->getTimestamp();
      $seconds_elapsed = $now_seconds - $end_seconds;

      if ($seconds_elapsed < 600) {
        $isLessThanTenMinutes = true;
      }
    }

    if (!$is_complete && !$is_in_progress) {
      $valid = false;
      $errors[] = "Unable to work on draft #$draft->draft_id: draft is $current_status_text";
    }

    if ($is_complete && !$isLessThanTenMinutes) {
      $valid = false;
      $errors[] = "Unable to work on draft #$draft->draft_id: draft is complete and beyond the ten minute grace period.";
    }

    return new HootDraftResponse($valid, $errors);
  }

  public function IsDraftComplete(Draft $draft) {
    $valid = true;
    $errors = array();
    $draft_statuses = $this->draftData->GetStatuses();
    $current_status_text = strtolower($draft_statuses[$draft->draft_status] ?? '');
    $is_complete = $this->draftService->DraftComplete($draft);

    if (!$is_complete) {
      $valid = false;
      $errors[] = "Unable to work on draft #$draft->draft_id: draft is $current_status_text";
    }

    return new HootDraftResponse($valid, $errors);
  }
}
