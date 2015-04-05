<?php

namespace Map;

use \Trades;
use \TradesQuery;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\InstancePoolTrait;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\DataFetcher\DataFetcherInterface;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Map\TableMapTrait;


/**
 * This class defines the structure of the 'trades' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class TradesTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = '.Map.TradesTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'phpdraft';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'trades';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Trades';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Trades';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 5;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 5;

    /**
     * the column name for the trade_id field
     */
    const COL_TRADE_ID = 'trades.trade_id';

    /**
     * the column name for the draft_id field
     */
    const COL_DRAFT_ID = 'trades.draft_id';

    /**
     * the column name for the manager1_id field
     */
    const COL_MANAGER1_ID = 'trades.manager1_id';

    /**
     * the column name for the manager2_id field
     */
    const COL_MANAGER2_ID = 'trades.manager2_id';

    /**
     * the column name for the trade_time field
     */
    const COL_TRADE_TIME = 'trades.trade_time';

    /**
     * The default string format for model objects of the related table
     */
    const DEFAULT_STRING_FORMAT = 'YAML';

    /**
     * holds an array of fieldnames
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldNames[self::TYPE_PHPNAME][0] = 'Id'
     */
    protected static $fieldNames = array (
        self::TYPE_PHPNAME       => array('TradeId', 'DraftId', 'Manager1Id', 'Manager2Id', 'TradeTime', ),
        self::TYPE_CAMELNAME     => array('tradeId', 'draftId', 'manager1Id', 'manager2Id', 'tradeTime', ),
        self::TYPE_COLNAME       => array(TradesTableMap::COL_TRADE_ID, TradesTableMap::COL_DRAFT_ID, TradesTableMap::COL_MANAGER1_ID, TradesTableMap::COL_MANAGER2_ID, TradesTableMap::COL_TRADE_TIME, ),
        self::TYPE_FIELDNAME     => array('trade_id', 'draft_id', 'manager1_id', 'manager2_id', 'trade_time', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('TradeId' => 0, 'DraftId' => 1, 'Manager1Id' => 2, 'Manager2Id' => 3, 'TradeTime' => 4, ),
        self::TYPE_CAMELNAME     => array('tradeId' => 0, 'draftId' => 1, 'manager1Id' => 2, 'manager2Id' => 3, 'tradeTime' => 4, ),
        self::TYPE_COLNAME       => array(TradesTableMap::COL_TRADE_ID => 0, TradesTableMap::COL_DRAFT_ID => 1, TradesTableMap::COL_MANAGER1_ID => 2, TradesTableMap::COL_MANAGER2_ID => 3, TradesTableMap::COL_TRADE_TIME => 4, ),
        self::TYPE_FIELDNAME     => array('trade_id' => 0, 'draft_id' => 1, 'manager1_id' => 2, 'manager2_id' => 3, 'trade_time' => 4, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, )
    );

    /**
     * Initialize the table attributes and columns
     * Relations are not initialized by this method since they are lazy loaded
     *
     * @return void
     * @throws PropelException
     */
    public function initialize()
    {
        // attributes
        $this->setName('trades');
        $this->setPhpName('Trades');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\Trades');
        $this->setPackage('');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('trade_id', 'TradeId', 'INTEGER', true, null, null);
        $this->addColumn('draft_id', 'DraftId', 'INTEGER', true, null, null);
        $this->addColumn('manager1_id', 'Manager1Id', 'INTEGER', true, null, null);
        $this->addColumn('manager2_id', 'Manager2Id', 'INTEGER', true, null, null);
        $this->addColumn('trade_time', 'TradeTime', 'TIMESTAMP', false, null, null);
    } // initialize()

    /**
     * Build the RelationMap objects for this table relationships
     */
    public function buildRelations()
    {
    } // buildRelations()

    /**
     * Retrieves a string version of the primary key from the DB resultset row that can be used to uniquely identify a row in this table.
     *
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, a serialize()d version of the primary key will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return string The primary key hash of the row
     */
    public static function getPrimaryKeyHashFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        // If the PK cannot be derived from the row, return NULL.
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('TradeId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('TradeId', TableMap::TYPE_PHPNAME, $indexType)];
    }

    /**
     * Retrieves the primary key from the DB resultset row
     * For tables with a single-column primary key, that simple pkey value will be returned.  For tables with
     * a multi-column primary key, an array of the primary key columns will be returned.
     *
     * @param array  $row       resultset row.
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM
     *
     * @return mixed The primary key of the row
     */
    public static function getPrimaryKeyFromRow($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        return (int) $row[
            $indexType == TableMap::TYPE_NUM
                ? 0 + $offset
                : self::translateFieldName('TradeId', TableMap::TYPE_PHPNAME, $indexType)
        ];
    }

    /**
     * The class that the tableMap will make instances of.
     *
     * If $withPrefix is true, the returned path
     * uses a dot-path notation which is translated into a path
     * relative to a location on the PHP include_path.
     * (e.g. path.to.MyClass -> 'path/to/MyClass.php')
     *
     * @param boolean $withPrefix Whether or not to return the path with the class name
     * @return string path.to.ClassName
     */
    public static function getOMClass($withPrefix = true)
    {
        return $withPrefix ? TradesTableMap::CLASS_DEFAULT : TradesTableMap::OM_CLASS;
    }

    /**
     * Populates an object of the default type or an object that inherit from the default.
     *
     * @param array  $row       row returned by DataFetcher->fetch().
     * @param int    $offset    The 0-based offset for reading from the resultset row.
     * @param string $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                 One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                           TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     * @return array           (Trades object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = TradesTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = TradesTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + TradesTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = TradesTableMap::OM_CLASS;
            /** @var Trades $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            TradesTableMap::addInstanceToPool($obj, $key);
        }

        return array($obj, $col);
    }

    /**
     * The returned array will contain objects of the default type or
     * objects that inherit from the default.
     *
     * @param DataFetcherInterface $dataFetcher
     * @return array
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function populateObjects(DataFetcherInterface $dataFetcher)
    {
        $results = array();

        // set the class once to avoid overhead in the loop
        $cls = static::getOMClass(false);
        // populate the object(s)
        while ($row = $dataFetcher->fetch()) {
            $key = TradesTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = TradesTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var Trades $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                TradesTableMap::addInstanceToPool($obj, $key);
            } // if key exists
        }

        return $results;
    }
    /**
     * Add all the columns needed to create a new object.
     *
     * Note: any columns that were marked with lazyLoad="true" in the
     * XML schema will not be added to the select list and only loaded
     * on demand.
     *
     * @param Criteria $criteria object containing the columns to add.
     * @param string   $alias    optional table alias
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function addSelectColumns(Criteria $criteria, $alias = null)
    {
        if (null === $alias) {
            $criteria->addSelectColumn(TradesTableMap::COL_TRADE_ID);
            $criteria->addSelectColumn(TradesTableMap::COL_DRAFT_ID);
            $criteria->addSelectColumn(TradesTableMap::COL_MANAGER1_ID);
            $criteria->addSelectColumn(TradesTableMap::COL_MANAGER2_ID);
            $criteria->addSelectColumn(TradesTableMap::COL_TRADE_TIME);
        } else {
            $criteria->addSelectColumn($alias . '.trade_id');
            $criteria->addSelectColumn($alias . '.draft_id');
            $criteria->addSelectColumn($alias . '.manager1_id');
            $criteria->addSelectColumn($alias . '.manager2_id');
            $criteria->addSelectColumn($alias . '.trade_time');
        }
    }

    /**
     * Returns the TableMap related to this object.
     * This method is not needed for general use but a specific application could have a need.
     * @return TableMap
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function getTableMap()
    {
        return Propel::getServiceContainer()->getDatabaseMap(TradesTableMap::DATABASE_NAME)->getTable(TradesTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(TradesTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(TradesTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new TradesTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a Trades or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or Trades object or primary key or array of primary keys
     *              which is used to create the DELETE statement
     * @param  ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
     public static function doDelete($values, ConnectionInterface $con = null)
     {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(TradesTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Trades) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(TradesTableMap::DATABASE_NAME);
            $criteria->add(TradesTableMap::COL_TRADE_ID, (array) $values, Criteria::IN);
        }

        $query = TradesQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            TradesTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                TradesTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the trades table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return TradesQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a Trades or Criteria object.
     *
     * @param mixed               $criteria Criteria or Trades object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(TradesTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from Trades object
        }

        if ($criteria->containsKey(TradesTableMap::COL_TRADE_ID) && $criteria->keyContainsValue(TradesTableMap::COL_TRADE_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.TradesTableMap::COL_TRADE_ID.')');
        }


        // Set the correct dbName
        $query = TradesQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // TradesTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
TradesTableMap::buildTableMap();
