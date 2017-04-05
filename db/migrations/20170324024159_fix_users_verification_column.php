<?php

use Phinx\Migration\AbstractMigration;

class FixUsersVerificationColumn extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('users');
        $users->renameColumn('verificiationKey', 'verificationKey')
                ->update();
    }
}
