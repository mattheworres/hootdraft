<?php

namespace HootDraft\Http\Check;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Validators\DraftValidator;
use HootDraft\Security\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** The signed-in user owns the draft or is an admin. Roles (ROLE_COMMISH) stay in security.yaml. */
class CommishEditableDraftCheck extends DraftCheck
{
  public function __construct(
    private DraftRepository $drafts,
    private DraftValidator $validator,
    private CurrentUser $currentUser,
  ) {
  }

  public static function alias(): string
  {
    return 'commishEditableDraft';
  }

  public function check(Request $request): ?Response
  {
    $draft = $this->drafts->Load($this->draftId($request));

    if ($this->validator->IsDraftEditableForUser($draft, $this->currentUser->get())) {
      return null;
    }

    $response = new HootDraftResponse(false, array());
    $response->errors[] = "You do not have permission to this draft.";

    return $this->failure($response);
  }
}
