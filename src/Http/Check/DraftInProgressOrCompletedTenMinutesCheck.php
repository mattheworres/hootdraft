<?php

namespace HootDraft\Http\Check;

use HootDraft\Domain\Repositories\DraftRepository;
use HootDraft\Domain\Validators\DraftValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Allows a ten minute grace period after completion of a draft (see the validator for a caveat).
class DraftInProgressOrCompletedTenMinutesCheck extends DraftCheck
{
  public function __construct(private DraftRepository $drafts, private DraftValidator $validator)
  {
  }

  public static function alias(): string
  {
    return 'draftInProgressOrCompletedTenMinutes';
  }

  public function check(Request $request): ?Response
  {
    $draft = $this->drafts->Load($this->draftId($request));

    $result = $this->validator->IsDraftInProgressOrCompletedInLessThanTen($draft);

    return $result->success ? null : $this->failure($result);
  }
}
