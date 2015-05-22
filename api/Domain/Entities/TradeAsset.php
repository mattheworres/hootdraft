<?php
namespace PhpDraft\Domain\Entities;

class TradeAsset {
  /** @var int */
  public $trade_asset_id;

  /** @var int */
  public $trade_id;

  /** @var int */
  protected $player_id;

  /** @var int */
  protected $oldmanager_id;

  /** @var int */
  protected $newmanager_id;

  /** @var player_object */
  public $player;

  /** @var manager_object */
  public $oldmanager;

  /** @var manager_object */
  public $newmanager;

  /** @var bool Used for loading from the DB */
  protected $was_drafted;
}