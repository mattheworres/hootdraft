<?php

use Phinx\Migration\AbstractMigration;

class CreateDrafts extends AbstractMigration
{
    public function change()
    {
        $draft = $this->table('draft', ['id' => 'draft_id']);
        
        $draft->addColumn('commish_id', 'integer', ['limit' => 11])
                ->addColumn('draft_create_time', 'datetime')
                ->addColumn('draft_name', 'text')
                ->addColumn('draft_sport', 'text')
                ->addColumn('draft_status', 'text')
                ->addColumn('draft_counter', 'integer', ['limit' => 11, 'default' => 0])
                ->addColumn('draft_style', 'text')
                ->addColumn('draft_rounds', 'integer', ['limit' => 2, 'signed' => false, 'default' => 0])
                ->addColumn('draft_password', 'text', ['null' => true, 'default' => null])
                ->addColumn('draft_start_time', 'datetime', ['null' => true, 'default' => null])
                ->addColumn('draft_end_time', 'datetime', ['null' => true, 'default' => null])
                ->addColumn('draft_stats_generated', 'datetime', ['null' => true, 'default' => null])
                ->addColumn('draft_current_round', 'integer', ['limit' => 5, 'default' => 1, 'signed' => false])
                ->addColumn('draft_current_pick', 'integer', ['limit' => 5, 'default' => 1, 'signed' => false])
                ->addColumn('nfl_extended', 'boolean', ['limit' => 1, 'default' => 0])
                ->addColumn('using_depth_charts', 'boolean', ['default' => 0])
                ->addIndex(['commish_id'])
                ->create();
    }
}
