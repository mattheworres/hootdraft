<?php

use Phinx\Migration\AbstractMigration;

class CreateProPlayers extends AbstractMigration
{
    public function change()
    {
        $pro_players = $this->table('pro_players', ['id' => 'pro_player_id', 'engine' => 'MyISAM']);
        
        $pro_players->addColumn('league', 'text')
                ->addColumn('first_name', 'text')
                ->addColumn('last_name', 'text')
                ->addColumn('position', 'text')
                ->addColumn('team', 'text')
                ->addIndex('league', ['limit' => 4, 'name' => 'league_idx'])
                ->addIndex('first_name', ['type' => 'fulltext', 'name' => 'firstname_idx'])
                ->addIndex('last_name', ['type' => 'fulltext', 'name' => 'lastname_idx'])
                ->create();
    }
}
