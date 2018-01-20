<?php

use Phinx\Migration\AbstractMigration;

class CreateUsers extends AbstractMigration
{
    public function change()
    {
        $users = $this->table('users');
        //Id column automatically created by Phinx
        $users->addColumn('enabled', 'boolean', ['limit' => 1, 'default' => 0])
                ->addColumn('email', 'string', ['limit' => 255])
                ->addColumn('password', 'string', ['limit' => 255])
                ->addColumn('salt', 'string', ['limit' => 16])
                ->addColumn('name', 'string', ['limit' => 100])
                ->addColumn('roles', 'string', ['limit' => 255])
                ->addColumn('verificiationKey', 'string', ['limit' => 16, 'null' => true, 'default' => null])
                ->addColumn('creationTime', 'datetime')
                ->addIndex('email', ['unique' => true, 'name' => 'unique_email'])
                ->create();
    }
}
