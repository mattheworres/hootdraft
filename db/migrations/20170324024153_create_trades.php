<?php

use Phinx\Migration\AbstractMigration;

class CreateTrades extends AbstractMigration
{
    public function change()
    {
        $trades = $this->table('trades', ['id' => 'trade_id']);
        
        $trades->addColumn('draft_id', 'integer', ['limit' => 11, 'signed' => false])
                ->addColumn('manager1_id', 'integer', ['limit' => 11, 'signed' => false])
                ->addColumn('manager2_id', 'integer', ['limit' => 11, 'signed' => false])
                ->addColumn('trade_time', 'datetime', ['null' => true])
                ->addColumn('trade_round', 'integer', ['limit' => 5])
                ->addIndex('manager1_id', ['name' => 'manager1_idx'])
                ->addIndex('manager2_id', ['name' => 'manager2_idx'])
                ->addIndex('draft_id', ['name' => 'draft_idx'])
                ->create();

    }
}
