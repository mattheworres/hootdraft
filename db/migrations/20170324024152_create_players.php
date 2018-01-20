<?php

use Phinx\Migration\AbstractMigration;

class CreatePlayers extends AbstractMigration
{
    public function change()
    {
        $players = $this->table('players', ['id' => 'player_id']);
        
        $players->addColumn('manager_id', 'integer', ['limit' => 11, 'default' => 0])
                ->addColumn('draft_id', 'integer', ['limit' => 11, 'signed' => false, 'default' => 0])
                ->addColumn('first_name', 'text', ['null' => true])
                ->addColumn('last_name', 'text', ['null' => true])
                ->addColumn('team', 'char', ['limit' => 3, 'null' => true])
                ->addColumn('position', 'string', ['limit' => 4, 'null' => true])
                ->addColumn('pick_time', 'datetime', ['null' => true])
                ->addColumn('pick_duration', 'integer', ['limit' => 10, 'null' => true])
                ->addColumn('player_counter', 'integer', ['limit' => 11, 'null' => true])
                ->addColumn('player_round', 'integer', ['limit' => 11, 'default' => 0])
                ->addColumn('player_pick', 'integer', ['limit' => 11, 'default' => 0])
                ->addColumn('depth_chart_position_id', 'integer', ['limit' => 11, 'null' => true])
                ->addColumn('position_eligibility', 'string', ['limit' => 24, 'null' => true])
                ->addIndex('manager_id', ['name' => 'manager_idx'])
                ->addIndex('draft_id', ['name' => 'draft_idx'])
                ->addIndex('player_counter', ['name' => 'counter_idx'])
                ->addIndex('depth_chart_position_id', ['name' => 'depth_chart_idx'])
                ->create();
    }
}
