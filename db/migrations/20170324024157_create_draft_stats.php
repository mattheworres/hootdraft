<?php

use Phinx\Migration\AbstractMigration;

class CreateDraftStats extends AbstractMigration
{
    public function change()
    {
        $draft_stats = $this->table('draft_stats', ['id' => 'draft_stat_id']);
        
        $draft_stats->addColumn('draft_id', 'integer', ['limit' => 11])
                ->addColumn('drafting_time_seconds', 'integer', ['limit' => 11])
                ->addColumn('longest_avg_pick_manager_name', 'text')
                ->addColumn('longest_avg_pick_seconds', 'integer', ['limit' => 11])
                ->addColumn('shortest_avg_pick_manager_name', 'text')
                ->addColumn('shortest_avg_pick_seconds', 'integer', ['limit' => 11])
                ->addColumn('longest_single_pick_manager_name', 'text')
                ->addColumn('longest_single_pick_seconds', 'integer', ['limit' => 11])
                ->addColumn('shortest_single_pick_manager_name', 'text')
                ->addColumn('shortest_single_pick_seconds', 'integer', ['limit' => 11])
                ->addColumn('average_pick_seconds', 'integer', ['limit' => 11])
                ->addColumn('longest_round', 'integer', ['limit' => 11])
                ->addColumn('longest_round_seconds', 'integer', ['limit' => 11])
                ->addColumn('shortest_round', 'integer', ['limit' => 11])
                ->addColumn('shortest_round_seconds', 'integer', ['limit' => 11])
                ->addColumn('average_round_seconds', 'integer', ['limit' => 11])
                ->addColumn('most_drafted_team', 'text')
                ->addColumn('most_drafted_team_count', 'integer', ['limit' => 11])
                ->addColumn('least_drafted_team', 'text')
                ->addColumn('least_drafted_team_count', 'integer', ['limit' => 11])
                ->addColumn('most_drafted_position', 'text')
                ->addColumn('most_drafted_position_count', 'integer', ['limit' => 11])
                ->addColumn('least_drafted_position', 'text')
                ->addColumn('least_drafted_position_count', 'integer', ['limit' => 11])
                ->create();
    }
}
