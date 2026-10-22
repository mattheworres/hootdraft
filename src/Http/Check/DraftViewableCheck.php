<?php

namespace HootDraft\Http\Check;

use HootDraft\Domain\Models\HootDraftResponse;
use HootDraft\Domain\Validators\DraftValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** The draft is public, or the viewer owns it / is an admin, or sent the right draft password header. */
class DraftViewableCheck extends DraftCheck
{
  public function __construct(private DraftValidator $validator)
  {
  }

  public static function alias(): string
  {
    return 'draftViewable';
  }

  public function check(Request $request): ?Response
  {
    if ($this->validator->IsDraftViewableForUser($this->draftId($request), $request)) {
      return null;
    }

    $response = new HootDraftResponse(false, array());
    $response->errors[] = "Draft marked as private.";

    return $this->failure($response);
  }
}
