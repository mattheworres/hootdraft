<?php

use Phinx\Migration\AbstractMigration;

class CreateRoundTimes extends AbstractMigration
{
    public function change()
    {
        $round_times = $this->table('round_times', ['id' => 'round_time_id']);
        
        $round_times->addColumn('draft_id', 'integer', ['limit' => 11])
                ->addColumn('is_static_time', 'boolean', ['limit' => 1, 'null' => true, 'default' => null])
                ->addColumn('draft_round', 'integer', ['limit' => 2, 'null' => true, 'default' => null])
                ->addColumn('round_time_seconds', 'integer', ['limit' => 11, 'null' => true, 'default' => null])
                ->addIndex('draft_id', ['name' => 'draft_idx'])
                ->addIndex('draft_round', ['name' => 'round_idx'])
                ->create();
    }
}
