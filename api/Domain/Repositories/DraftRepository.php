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
  */
  public function Load($id) {
    $draft = new Draft();

    $draft_stmt = $this->app['db']->prepare("SELECT * FROM draft WHERE draft_id = ? LIMIT 1");
    $draft_stmt->setFetchMode(\PDO::FETCH_INTO, $draft);

    $draft_stmt->bindParam(1, $id, \PDO::PARAM_INT);

    if(!$draft_stmt->execute() || !$draft_stmt->fetch()) {
      throw new \Exception("Unable to load draft");
    }

    return $draft;
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

    return $draft;
  }
}