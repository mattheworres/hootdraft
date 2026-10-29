<?php

use Phinx\Migration\AbstractMigration;

class DropPlayersNameFulltextIndex extends AbstractMigration
{
    public function up()
    {
        // The pick search no longer uses MATCH ... AGAINST. See docs/adr/0003.
        $this->execute('ALTER TABLE players DROP INDEX firstlastname_idx');
    }

    public function down()
    {
        $this->execute('ALTER TABLE players ADD FULLTEXT INDEX firstlastname_idx (first_name, last_name)');
    }
}
