<?php

namespace HootDraft\Http\Check;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A guard that runs before a controller (the old Silex ->before() closures: draftViewable,
 * commishEditableDraft, ...). Return a Response to short-circuit the request, or null to continue.
 */
interface RouteCheck
{
  /** The name used in a route's `_checks` default, e.g. 'draftViewable'. */
  public static function alias(): string;

  public function check(Request $request): ?Response;
}
