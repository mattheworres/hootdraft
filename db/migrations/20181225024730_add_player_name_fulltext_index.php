<?php

use Phinx\Migration\AbstractMigration;

class AddPlayerNameFulltextIndex extends AbstractMigration
{
    public function change()
    {
      $players = $this->table('players');

      $players->addIndex(['first_name', 'last_name'], ['type' => 'fulltext', 'name' => 'firstlastname_idx'])
              ->update();
    }
}
