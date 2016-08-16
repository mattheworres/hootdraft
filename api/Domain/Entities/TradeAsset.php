<?php
namespace PhpDraft\Domain\Entities;

class TradeAsset {
  public function __toString() {
    return (string)$this->player->player_id;
  }

  /** @var int */
  public $trade_asset_id;

  /** @var int */
  public $trade_id;

  /** @var int */
  public $oldmanager_id;

  /** @var int */
  public $newmanager_id;

  /** @var Player */
  public $player;

  /** @var Manager */
  public $oldmanager;

  /** @var Manager */
  public $newmanager;

  /** @var bool */
  public $was_drafted;
}