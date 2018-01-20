<?php

use Phinx\Migration\AbstractMigration;

class CreateTradeAssets extends AbstractMigration
{
    public function change()
    {
        $trade_assets = $this->table('trade_assets', ['id' => 'tradeasset_id']);
        
        $trade_assets->addColumn('trade_id', 'integer', ['limit' => 11, 'signed' => false])
                ->addColumn('player_id', 'integer', ['limit' => 11, 'signed' => false])
                ->addColumn('oldmanager_id', 'integer', ['limit' => 11, 'signed' => false])
                ->addColumn('newmanager_id', 'integer', ['limit' => 11, 'signed' => false])
                ->addColumn('was_drafted', 'boolean', ['limit' => 1, 'default' => 0])
                ->addIndex(['trade_id', 'player_id', 'oldmanager_id', 'newmanager_id'])
                ->create();
    }
}
