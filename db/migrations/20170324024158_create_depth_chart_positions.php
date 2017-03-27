<?php

use Phinx\Migration\AbstractMigration;
use Phinx\Db\Adapter\MysqlAdapter;

class CreateDepthChartPositions extends AbstractMigration
{
    public function change()
    {
        $depth_chart_positions = $this->table('depth_chart_positions');
        //Id column automatically created by Phinx
        $depth_chart_positions->addColumn('draft_id', 'integer', ['limit' => 11, 'default' => 0])
                ->addColumn('position', 'string', ['limit' => 6])
                ->addColumn('slots', 'integer', ['limit' => 5])
                ->addColumn('display_order', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'default' => 0, 'signed' => false])
                ->addIndex('draft_id', ['name' => 'draft_idx'])
                ->create();

    }
}
