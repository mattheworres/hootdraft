<?php
namespace PhpDraft\Domain\Validators;

use \Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use PhpDraft\Domain\Entities\LoginUser;
use PhpDraft\Domain\Entities\Draft;
use PhpDraft\Domain\Models\PhpDraftResponse;
use Symfony\Component\Security\Core\Util\StringUtils;
use Egulias\EmailValidator\EmailValidator;

class DraftValidator {
  private $app;

  public function __construct(Application $app) {
    $this->app = $app;
  }

  public function IsDraftViewableForUser($draft_id, Request $request) {
    $draft = $this->app['phpdraft.DraftRepository']->Load($draft_id);
    $current_user = $this->app['phpdraft.LoginUserService']->GetUserFromHeaderToken($request);
    $draft_password = $request->headers->get($this->app['phpdraft.draft_password'], '');

    if(!empty($current_user) && $draft->commish_id == $current_user->id) {
      return true;
    }

    if(empty($draft->draft_password) || ($draft->draft_password == $draft_password)) {
      return true;
    }

    return false;
  }

  public function IsDraftEditableForUser(Draft $draft, LoginUser $current_user) {
    if(!empty($current_user) && !empty($draft) && $draft->commish_id == $current_user->id) {
      return true;
    }

    if(!empty($current_user) && $this->app['phpdraft.LoginUserService']->CurrentUserIsAdmin($current_user)) {
      return true;
    }

    return false;
  }
}