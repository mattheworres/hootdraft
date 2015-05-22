<?php
namespace PhpDraft\Domain\Repositories;

use Silex\Application;
use PhpDraft\Domain\Entities\Draft;

class DraftRepository {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  //TODO: Add server-side paging
  public function GetPublicDrafts(/*$pageSize = 25, $page = 1*/) {
    /*$page = (int)$page;
    $pageSize = (int)$pageSize;
    $startIndex = ($page-1) * $pageSize;

    if($startIndex < 0) {
      throw new \Exception("Unable to get drafts: incorrect paging parameters.");
    }*/

    //$draft_stmt = $this->app['db']->prepare("SELECT * FROM draft ORDER BY draft_create_time LIMIT ?, ?");
    $draft_stmt = $this->app['db']->prepare("SELECT * FROM draft ORDER BY draft_create_time");

    $draft_stmt->setFetchMode(\PDO::FETCH_CLASS, '\PhpDraft\Domain\Entities\Draft');

    /*$draft_stmt->bindParam(1, $startIndex, \PDO::PARAM_INT);
    $draft_stmt->bindParam(2, $pageSize, \PDO::PARAM_INT);*/

    if(!$draft_stmt->execute()) {
      throw new \Exception("Unable to load drafts.");
    }

    while($draft = $draft_stmt->fetch()) {
      $draft->draft_visible = empty($draft->draft_password);

      if(!$draft->draft_visible) {
        $draft = $this->ProtectPrivateDraft($draft);
      }

      unset($draft->draft_password);

      $drafts[] = $draft;
    }

    return $drafts;
  }

  public function GetPublicDraft($id, $password = '') {
    $draft = new Draft();

    $draft_stmt = $this->app['db']->prepare("SELECT * FROM draft WHERE draft_id = ? LIMIT 1");
    $draft_stmt->setFetchMode(\PDO::FETCH_INTO, $draft);

    $draft_stmt->bindParam(1, $id, \PDO::PARAM_INT);

    if(!$draft_stmt->execute() || !$draft_stmt->fetch()) {
      throw new \Exception("Unable to load draft");
    }

    $draft->draft_visible = empty($draft->draft_password);

    if(!$draft->draft_visible && $password != $draft->draft_password) {
      $draft = $this->ProtectPrivateDraft($draft);
    }

    unset($draft->draft_password);

    return $draft;
  }

  private function ProtectPrivateDraft(Draft $draft) {
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