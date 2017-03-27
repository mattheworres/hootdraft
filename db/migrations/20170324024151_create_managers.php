<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateManagers extends AbstractMigration
{
    public function change()
    {
        $managers = $this->table('managers', ['id' => 'manager_id']);
        
        $managers->addColumn('draft_id', 'integer', ['limit' => 11, 'default' => 0])
                ->addColumn('manager_name', 'text')
                ->addColumn('draft_order', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'signed' => false, 'default' => 0])
                ->addIndex(['draft_id'], ['name' => 'draft_idx'])
                ->create();
    }
}
