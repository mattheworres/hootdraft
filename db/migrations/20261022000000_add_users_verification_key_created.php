<?php

use Phinx\Migration\AbstractMigration;

class AddUsersVerificationKeyCreated extends AbstractMigration
{
    public function up()
    {
        $this->table('users')
            ->addColumn('verificationKeyCreated', 'datetime', ['null' => true, 'default' => null])
            ->update();

        // Keys that are already out in emails get a fresh window instead of expiring the moment this deploys
        $this->execute('UPDATE users SET verificationKeyCreated = UTC_TIMESTAMP() WHERE verificationKey IS NOT NULL');
    }

    public function down()
    {
        $this->table('users')->removeColumn('verificationKeyCreated')->update();
    }
}
