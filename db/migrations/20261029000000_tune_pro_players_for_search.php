<?php

use Phinx\Migration\AbstractMigration;

class TuneProPlayersForSearch extends AbstractMigration
{
    public function up()
    {
        // The two FULLTEXT indexes were never used: every search is LIKE '%term%', which cannot use them.
        // text columns cannot carry a full-length index, so the name columns become varchar first.
        // Every search filters on league and then scans that league's names, so (league, name) indexes serve it.
        $this->execute('ALTER TABLE pro_players
            MODIFY league VARCHAR(16) NOT NULL,
            MODIFY first_name VARCHAR(100) NOT NULL,
            MODIFY last_name VARCHAR(100) NOT NULL,
            MODIFY position VARCHAR(16) NOT NULL,
            MODIFY team VARCHAR(16) NOT NULL,
            DROP INDEX league_idx,
            DROP INDEX firstname_idx,
            DROP INDEX lastname_idx,
            ADD INDEX league_last_idx (league, last_name),
            ADD INDEX league_first_idx (league, first_name)');

        // utf8mb4_general_ci compares faster than utf8mb4_unicode_ci. Searches still ignore case and accents.
        $this->execute('ALTER TABLE pro_players CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
    }

    public function down()
    {
        $this->execute('ALTER TABLE pro_players CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->execute('ALTER TABLE pro_players
            MODIFY league TEXT NULL,
            MODIFY first_name TEXT NULL,
            MODIFY last_name TEXT NULL,
            MODIFY position TEXT NULL,
            MODIFY team TEXT NULL,
            DROP INDEX league_last_idx,
            DROP INDEX league_first_idx,
            ADD INDEX league_idx (league(4)),
            ADD FULLTEXT INDEX firstname_idx (first_name),
            ADD FULLTEXT INDEX lastname_idx (last_name)');
    }
}
