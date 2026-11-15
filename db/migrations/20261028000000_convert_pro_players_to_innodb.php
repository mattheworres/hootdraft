<?php

use Phinx\Migration\AbstractMigration;

class ConvertProPlayersToInnodb extends AbstractMigration
{
    public function up()
    {
        // MyISAM ignores transactions, so a failed CSV upload could leave a league's players deleted.
        // InnoDB supports the FULLTEXT indexes from MySQL 5.6 on.
        $this->execute('ALTER TABLE pro_players ENGINE=InnoDB');
    }

    public function down()
    {
        $this->execute('ALTER TABLE pro_players ENGINE=MyISAM');
    }
}
