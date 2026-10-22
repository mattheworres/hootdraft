<?php

namespace HootDraft\Http\Check;

use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Validators\DraftValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Ported 1:1 from the old `draftSettingUp` closure, which called IsDraftSettingUpOrInProgress and so let an
// in-progress draft through. Renamed to say so; tightening it would change what commissioners can edit mid-draft.
class DraftSettingUpOrInProgressCheck extends DraftCheck
{
  public function __construct(private DraftRepository $drafts, private DraftValidator $validator)
  {
  }

  public static function alias(): string
  {
    return 'draftSettingUp';
  }

  public function check(Request $request): ?Response
  {
    $draft = $this->drafts->Load($this->draftId($request));

    $result = $this->validator->IsDraftSettingUpOrInProgress($draft);

    return $result->success ? null : $this->failure($result);
  }
}
