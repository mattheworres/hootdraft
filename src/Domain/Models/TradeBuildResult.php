<?php

namespace HootDraft\Domain\Models;

use HootDraft\Domain\Entities\Trade;

/** What TradeService::BuildTrade gives back: the built and valid Trade, or the errors that refused it. Never both. */
final class TradeBuildResult
{
  /** @param string[] $errors */
  private function __construct(
    public readonly ?Trade $trade,
    public readonly array $errors,
  ) {
  }

  public static function Built(Trade $trade): self
  {
    return new self($trade, []);
  }

  /** @param string[] $errors */
  public static function Refused(array $errors): self
  {
    return new self(null, $errors);
  }
}
