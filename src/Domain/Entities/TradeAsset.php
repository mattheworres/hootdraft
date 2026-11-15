<?php
namespace HootDraft\Domain\Entities;

// Rows loaded from trade_assets also carry player_id as a dynamic property.
#[\AllowDynamicProperties]
class TradeAsset {
  public function __toString() {
    return (string)$this->player->player_id;
  }

  /** @var int */
  public $tradeasset_id;

  /** @var int */
  public $trade_id;

  /** @var int */
  public $oldmanager_id;

  /** @var int */
  public $newmanager_id;

  /** @var Pick */
  public $player;

  /** @var Manager */
  public $oldmanager;

  /** @var Manager */
  public $newmanager;

  /** @var bool */
  public $was_drafted;
}
