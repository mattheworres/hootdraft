<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Entities\Pick;

class DraftRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  //TODO: Add server-side paging
  public function GetPublicDrafts(Request $request/*$pageSize = 25, $page = 1*/, $password = '') {
    /*$page = (int)$page;
    $pageSize = (int)$pageSize;
    $startIndex = ($page-1) * $pageSize;

    if($startIndex < 0) {
      throw new \Exception("Unable to get drafts: incorrect paging parameters.");
    }*/

    //$draft_stmt = $this->app['db']->prepare("SELECT * FROM draft ORDER BY draft_create_time LIMIT ?, ?");
    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d 
      LEFT OUTER JOIN users u 
      ON d.commish_id = u.id 
      ORDER BY draft_create_time DESC");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    /*$draft_stmt->bindParam(1, $startIndex, \PDO::PARAM_INT);
    $draft_stmt->bindParam(2, $pageSize, \PDO::PARAM_INT);*/

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;
      $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

      $draft->draft_visible = empty($draft->draft_password);
      $draft->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin;
      $draft->setting_up = $this->app['phpdraft.DraftService']->DraftSettingUp($draft);
      $draft->in_progress = $this->app['phpdraft.DraftService']->DraftInProgress($draft);
      $draft->complete = $this->app['phpdraft.DraftService']->DraftComplete($draft);
      $draft->is_locked = false;

      $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
      $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
      $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

      if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$draft->draft_visible && $password != $draft->draft_password) {
        $draft->is_locked = true;
        $draft = $this->ProtectPrivateDraft($draft);
      }

      unset($draft->draft_password);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  public function GetPublicDraftsByCommish(Request $request, $commish_id, $password = '') {
    $commish_id = (int)$commish_id;

    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
    LEFT OUTER JOIN users u
    ON d.commish_id = u.id
    WHERE commish_id = ?
    ORDER BY draft_create_time DESC");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');
    $draft_stmt->bindParam(1, $commish_id);

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;
      $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

      $draft->draft_visible = empty($draft->draft_password);
      $draft->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin;
      $draft->setting_up = $this->app['phpdraft.DraftService']->DraftSettingUp($draft);
      $draft->in_progress = $this->app['phpdraft.DraftService']->DraftInProgress($draft);
      $draft->complete = $this->app['phpdraft.DraftService']->DraftComplete($draft);
      $draft->is_locked = false;

      $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
      $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
      $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

      if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$draft->draft_visible && $password != $draft->draft_password) {
        $draft->is_locked = true;
        $draft = $this->ProtectPrivateDraft($draft);
      }

      unset($draft->draft_password);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  //Note: this method is to be used by admin section only
  public function GetAllDraftsByCommish($commish_id) {
    $commish_id = (int)$commish_id;

    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
    LEFT OUTER JOIN users u
    ON d.commish_id = u.id
    WHERE commish_id = ?
    ORDER BY draft_create_time DESC");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');
    $draft_stmt->bindParam(1, $commish_id);

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
      $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
      $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  //Note: this method is to be used by admin section only
  public function GetAllCompletedDrafts() {
    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
      LEFT OUTER JOIN users u
      ON d.commish_id = u.id
      WHERE d.draft_status = 'complete'
      ORDER BY draft_create_time DESC");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
      $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
      $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  public function GetPublicDraft(Request $request, $id, $getDraftData = false, $password = '') {
    $draft = new Draft();

    $cachedDraft = $this->GetCachedDraft($id);

    if($cachedDraft != null) {
      $draft = $cachedDraft;
    } else {
      $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
        LEFT OUTER JOIN users u
        ON d.commish_id = u.id
        WHERE d.draft_id = ? LIMIT 1");
      $draft_stmt->setFetchMode(\PDO::FETCH_INTO, $draft);

      $draft_stmt->bindParam(1, $id, \PDO::PARAM_INT);

      if(!$draft_stmt->execute() || !$draft_stmt->fetch()) {
        throw new \Exception("Unable to load draft");
      }

      $this->SetCachedDraft($draft);
    }

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;
    $currentUserIsAdmin = !empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user);

    $draft->draft_visible = empty($draft->draft_password);
    $draft->commish_editable = $currentUserOwnsIt || $currentUserIsAdmin;

    $draft->draft_create_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_create_time);
    $draft->draft_start_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_start_time);
    $draft->draft_end_time = $this->app['phpdraft.UtilityService']->ConvertTimeForClientDisplay($draft->draft_end_time);

    $draft->setting_up = $this->app['phpdraft.DraftService']->DraftSettingUp($draft);
    $draft->in_progress = $this->app['phpdraft.DraftService']->DraftInProgress($draft);
    $draft->complete = $this->app['phpdraft.DraftService']->DraftComplete($draft);

    if($getDraftData) {
      $draft->sports = $this->app['phpdraft.DraftDataRepository']->GetSports();
      $draft->styles = $this->app['phpdraft.DraftDataRepository']->GetStyles();
      $draft->statuses = $this->app['phpdraft.DraftDataRepository']->GetStatuses();
      $draft->teams = $this->app['phpdraft.DraftDataRepository']->GetTeams($draft->draft_sport);
      $draft->positions = $this->app['phpdraft.DraftDataRepository']->GetPositions($draft->draft_sport);
    }

    $draft->is_locked = false;

    if(!$currentUserOwnsIt && !$currentUserIsAdmin && !$draft->draft_visible && $password != $draft->draft_password) {
      $draft->is_locked = true;
      $draft = $this->ProtectPrivateDraft($draft);
    }

    unset($draft->draft_password);

    return $draft;
  }

  /*
  * This method is only to be used internally or when the user has been verified as owner of the draft (or is admin)
  * (in other words, don't call this then return the result as JSON!)
  */
  public function Load($id, $bustCache = false) {
    $draft = new Draft();

    $cachedDraft = $this->GetCachedDraft($id);

    if($bustCache || $cachedDraft == null) {
      $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
      LEFT OUTER JOIN users u
      ON d.commish_id = u.id
      WHERE draft_id = ? LIMIT 1");

      $draft_stmt->setFetchMode(\PDO::FETCH_INTO, $draft);

      $draft_stmt->bindParam(1, $id, \PDO::PARAM_INT);

      if(!$draft_stmt->execute() || !$draft_stmt->fetch()) {
        throw new \Exception("Unable to load draft");
      }

      if($bustCache) {
        $this->UnsetCachedDraft($draft->draft_id);
      }

      $this->SetCachedDraft($draft);
    } else {
      $draft = $cachedDraft;
    }

    $draft->draft_rounds = (int)$draft->draft_rounds;

    return $draft;
  }

  public function Create(Draft $draft) {
    $insert_stmt = $this->app['db']->prepare("INSERT INTO draft
      (draft_id, commish_id, draft_create_time, draft_name, draft_sport, draft_status, draft_style, draft_rounds, draft_password)
      VALUES
      (NULL, ?, UTC_TIMESTAMP(), ?, ?, ?, ?, ?, ?)");

    $insert_stmt->bindParam(1, $draft->commish_id);
    $insert_stmt->bindParam(2, $draft->draft_name);
    $insert_stmt->bindParam(3, $draft->draft_sport);
    $insert_stmt->bindParam(4, $draft->draft_status);
    $insert_stmt->bindParam(5, $draft->draft_style);
    $insert_stmt->bindParam(6, $draft->draft_rounds);
    $insert_stmt->bindParam(7, $draft->draft_password);

    if(!$insert_stmt->execute()) {
      throw new \Exception("Unable to create draft.");
    }

    $draft = $this->Load((int)$this->app['db']->lastInsertId(), true);

    return $draft;
  }

  //Excluded properties in update:
  //draft_start_time/draft_end_time - updated in separate operations at start/end of draft
  //draft_current_round/draft_current_pick - updated when new picks are made
  //draft_counter - call IncrementDraftCounter instead - this call's made a lot independently of other properties.
  //draft_status - separate API call to update the draft status
  public function Update(Draft $draft) {
    $update_stmt = $this->app['db']->prepare("UPDATE draft
      SET commish_id = ?, draft_name = ?, draft_sport = ?,
      draft_style = ?, draft_password = ?, draft_rounds = ?
      WHERE draft_id = ?");

    $update_stmt->bindParam(1, $draft->commish_id);
    $update_stmt->bindParam(2, $draft->draft_name);
    $update_stmt->bindParam(3, $draft->draft_sport);
    $update_stmt->bindParam(4, $draft->draft_style);
    $update_stmt->bindParam(5, $draft->draft_password);
    $update_stmt->bindParam(6, $draft->draft_rounds);
    $update_stmt->bindParam(7, $draft->draft_id);

    if(!$update_stmt->execute()) {
      throw new \Exception("Unable to update draft.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return $draft;
  }

  public function UpdateStatus(Draft $draft) {
    $status_stmt = $this->app['db']->prepare("UPDATE draft
      SET draft_status = ? WHERE draft_id = ?");

    $status_stmt->bindParam(1, $draft->draft_status);
    $status_stmt->bindParam(2, $draft->draft_id);

    if(!$status_stmt->execute()) {
      throw new \Exception("Unable to update draft status.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return $draft;
  }

  public function UpdateStatsTimestamp(Draft $draft) {
    $status_stmt = $this->app['db']->prepare("UPDATE draft
      SET draft_stats_generated = UTC_TIMESTAMP() WHERE draft_id = ?");

    $status_stmt->bindParam(1, $draft->draft_id);

    if(!$status_stmt->execute()) {
      throw new \Exception("Unable to update draft's stats timestamp.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return $draft;
  }

  public function IncrementDraftCounter(Draft $draft) {
    $incrementedCounter = (int)$draft->draft_counter + 1;

    $increment_stmt = $this->app['db']->prepare("UPDATE draft
      SET draft_counter = ? WHERE draft_id = ?");

    $increment_stmt->bindParam(1, $incrementedCounter);
    $increment_stmt->bindParam(2, $draft->draft_id);

    if(!$increment_stmt->execute()) {
      throw new \Exception("Unable to increment draft counter.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return $incrementedCounter;
  }

  //$next_pick can't be type-hinted - can be null
  public function MoveDraftForward(Draft $draft, $next_pick) {
    if ($next_pick !== null) {
      $draft->draft_current_pick = (int) $next_pick->player_pick;
      $draft->draft_current_round = (int) $next_pick->player_round;

      $stmt = $this->app['db']->prepare("UPDATE draft SET draft_current_pick = ?, draft_current_round = ? WHERE draft_id = ?");
      $stmt->bindParam(1, $draft->draft_current_pick);
      $stmt->bindParam(2, $draft->draft_current_round);
      $stmt->bindParam(3, $draft->draft_id);

      if (!$stmt->execute()) {
        throw new \Exception("Unable to move draft forward.");
      }
    } else {
      $draft->draft_status = 'complete';
      $stmt = $this->app['db']->prepare("UPDATE draft SET draft_status = ?, draft_end_time = UTC_TIMESTAMP() WHERE draft_id = ?");
      $stmt->bindParam(1, $draft->draft_status);
      $stmt->bindParam(2, $draft->draft_id);

      if (!$stmt->execute()) {
        throw new \Exception("Unable to move draft forward.");
      }
    }

    $this->ResetDraftCache($draft->draft_id);

    return $draft;
  }

  //Used when we move a draft from "undrafted" to "in_progress":
  //Resets the draft counter
  //Sets the current pick and round to 1
  //Sets the draft start time to UTC now, nulls out end time
  public function SetDraftInProgress(Draft $draft) {
    $reset_stmt = $this->app['db']->prepare("UPDATE draft
      SET draft_counter = 0, draft_current_pick = 1, draft_current_round = 1,
      draft_start_time = UTC_TIMESTAMP(), draft_end_time = NULL
      WHERE draft_id = ?");

    $reset_stmt->bindParam(1, $draft->draft_id);

    if(!$reset_stmt->execute()) {
      throw new \Exception("Unable to set draft to in progress.");
    }

    $this->ResetDraftCache($draft->draft_id);

    return 0;
  }

  public function NameIsUnique($name, $id = null) {
    if(!empty($id)) {
      $name_stmt = $this->app['db']->prepare("SELECT draft_name FROM draft WHERE draft_name LIKE ? AND draft_id <> ?");
      $name_stmt->bindParam(1, $name);
      $name_stmt->bindParam(2, $id);
    } else {
      $name_stmt = $this->app['db']->prepare("SELECT draft_name FROM draft WHERE draft_name LIKE ?");
      $name_stmt->bindParam(1, $name);
    }

    if(!$name_stmt->execute()) {
      throw new \Exception("Draft name '%s' is invalid", $name);
    }

    return $name_stmt->rowCount() == 0;
  }

  public function DeleteDraft($draft_id) {
    $delete_stmt = $this->app['db']->prepare("DELETE FROM draft WHERE draft_id = ?");
    $delete_stmt->bindParam(1, $draft_id);

    if(!$delete_stmt->execute()) {
      throw new \Exception("Unable to delete draft $draft_id.");
    }

    $this->UnsetCachedDraft($draft_id);

    return;
  }

  private function ResetDraftCache($draft_id) {
    $draft = $this->Load($draft_id, true);
  }

  private function SetCachedDraft(Draft $draft) {
    $this->app['phpdraft.ObjectCache']->set("draft$draft->draft_id", $draft, CACHE_SECONDS);
  }

  private function GetCachedDraft($draft_id) {
    return $this->app['phpdraft.ObjectCache']->get("draft$draft_id");
  }

  private function UnsetCachedDraft($draft_id) {
    $this->app['phpdraft.ObjectCache']->delete("draft$draft_id");
  }

  private function ProtectPrivateDraft(Draft $draft) {
    $draft->draft_sport = '';
    $draft->draft_status = '';
    $draft->setting_up = '';
    $draft->in_progress = '';
    $draft->complete = '';
    $draft->draft_style = '';
    $draft->draft_rounds = '';
    $draft->draft_counter = '';
    $draft->draft_start_time = null;
    $draft->draft_end_time = null;
    $draft->draft_current_pick = '';
    $draft->draft_current_round = '';
    $draft->draft_create_time = '';
    $draft->draft_stats_generated = '';
    $draft->nfl_extended = null;
    $draft->sports = null;
    $draft->styles = null;
    $draft->statuses = null;
    $draft->teams = null;
    $draft->positions = null;

    return $draft;
  }
}