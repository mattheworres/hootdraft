<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\Draft;

class DraftRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  //TODO: Add server-side paging
  public function GetPublicDrafts(Request $request/*$pageSize = 25, $page = 1*/) {
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
      ORDER BY draft_create_time");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    /*$draft_stmt->bindParam(1, $startIndex, \PDO::PARAM_INT);
    $draft_stmt->bindParam(2, $pageSize, \PDO::PARAM_INT);*/

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $draft->draft_visible = empty($draft->draft_password);

      $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;

      if(!$currentUserOwnsIt && !$draft->draft_visible) {
        $draft = $this->ProtectPrivateDraft($draft);
      }

      unset($draft->draft_password);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  public function GetPublicDraftsByCommish(Request $request, $commish_id) {
    $commish_id = (int)$commish_id;

    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
    LEFT OUTER JOIN users u
    ON d.commish_id = u.id
    WHERE commish_id = ?
    ORDER BY draft_create_time");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');
    $draft_stmt->bindParam(1, $commish_id);

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    $drafts = array();

    while($draft = $draft_stmt->fetch()) {
      $draft->draft_visible = empty($draft->draft_password);

      $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;

      if(!$currentUserOwnsIt && !$draft->draft_visible) {
        $draft = $this->ProtectPrivateDraft($draft);
      }

      unset($draft->draft_password);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  public function GetPublicDraft(Request $request, $id, $password = '') {
    $draft = new Draft();

    $draft_stmt = $this->app['db']->prepare("SELECT * FROM draft WHERE draft_id = ? LIMIT 1");
    $draft_stmt->setFetchMode(\PDO::FETCH_INTO, $draft);

    $draft_stmt->bindParam(1, $id, \PDO::PARAM_INT);

    if(!$draft_stmt->execute() || !$draft_stmt->fetch()) {
      throw new \Exception("Unable to load draft");
    }

    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);

    $currentUserOwnsIt = !empty($current_user) && $draft->commish_id == $current_user->id;

    $draft->draft_visible = empty($draft->draft_password);

    if(!$currentUserOwnsIt && !$draft->draft_visible && $password != $draft->draft_password) {
      
      $draft = $this->ProtectPrivateDraft($draft);
    }

    unset($draft->draft_password);

    return $draft;
  }

  /*
  * This method is only to be used internally or when the user has been verified as owner of the draft (or is admin)
  * (in other words, don't call this then return the result as JSON!)
  */
  public function Load($id) {
    $draft = new Draft();

    $draft_stmt = $this->app['db']->prepare("SELECT d.*, u.Name AS commish_name FROM draft d
    LEFT OUTER JOIN users u
    ON d.commish_id = u.id
    WHERE draft_id = ? LIMIT 1");

    $draft_stmt->setFetchMode(\PDO::FETCH_INTO, $draft);

    $draft_stmt->bindParam(1, $id, \PDO::PARAM_INT);

    if(!$draft_stmt->execute() || !$draft_stmt->fetch()) {
      throw new \Exception("Unable to load draft");
    }

    return $draft;
  }

  public function Create(Draft $draft) {
    $insert_stmt = $this->app['db']->prepare("INSERT INTO draft
      (draft_id, commish_id, draft_create_time, draft_name, draft_sport, draft_status, draft_style, draft_rounds, draft_password, nfl_extended)
      VALUES
      (NULL, ?, UTC_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)");

    $insert_stmt->bindParam(1, $draft->commish_id);
    $insert_stmt->bindParam(2, $draft->draft_name);
    $insert_stmt->bindParam(3, $draft->draft_sport);
    $insert_stmt->bindParam(4, $draft->draft_status);
    $insert_stmt->bindParam(5, $draft->draft_style);
    $insert_stmt->bindParam(6, $draft->draft_rounds);
    $insert_stmt->bindParam(7, $draft->draft_password);
    $insert_stmt->bindParam(8, $draft->nfl_extended);

    if(!$insert_stmt->execute()) {
      throw new \Exception("Unable to create draft.");
    }

    $draft->draft_id = (int)$this->app['db']->lastInsertId();

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
      draft_style = ?, draft_password = ?, nfl_extended = ?, draft_rounds = ?
      WHERE draft_id = ?");

    $update_stmt->bindParam(1, $draft->commish_id);
    $update_stmt->bindParam(2, $draft->draft_name);
    $update_stmt->bindParam(3, $draft->draft_sport);
    $update_stmt->bindParam(4, $draft->draft_style);
    $update_stmt->bindParam(5, $draft->draft_password);
    $update_stmt->bindParam(6, $draft->nfl_extended);
    $update_stmt->bindParam(7, $draft->draft_rounds);
    $update_stmt->bindParam(8, $draft->draft_id);

    if(!$update_stmt->execute()) {
      throw new \Exception("Unable to update draft.");
    }

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

    return $incrementedCounter;
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

    return;
  }

  private function ProtectPrivateDraft(Draft $draft) {
    $draft->commish_id = 0;
    $draft->commish_name = '';
    $draft->draft_sport = '';
    $draft->draft_status = '';
    $draft->draft_style = '';
    $draft->draft_rounds = '';
    $draft->draft_counter = '';
    $draft->draft_start_time = null;
    $draft->draft_end_time = null;
    $draft->draft_current_pick = '';
    $draft->draft_current_round = '';
    $draft->draft_create_time = '';
    $draft->nfl_extended = null;

    return $draft;
  }
}