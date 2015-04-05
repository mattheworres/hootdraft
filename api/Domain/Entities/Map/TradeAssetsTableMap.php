<?php

namespace Map;

use \TradeAssets;
use \TradeAssetsQuery;
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
 * This class defines the structure of the 'trade_assets' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class TradeAssetsTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = '.Map.TradeAssetsTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'phpdraft';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'trade_assets';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\TradeAssets';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'TradeAssets';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 6;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 6;

    /**
     * the column name for the tradeasset_id field
     */
    const COL_TRADEASSET_ID = 'trade_assets.tradeasset_id';

    /**
     * the column name for the trade_id field
     */
    const COL_TRADE_ID = 'trade_assets.trade_id';

    /**
     * the column name for the player_id field
     */
    const COL_PLAYER_ID = 'trade_assets.player_id';

    /**
     * the column name for the oldmanager_id field
     */
    const COL_OLDMANAGER_ID = 'trade_assets.oldmanager_id';

    /**
     * the column name for the newmanager_id field
     */
    const COL_NEWMANAGER_ID = 'trade_assets.newmanager_id';

    /**
     * the column name for the was_drafted field
     */
    const COL_WAS_DRAFTED = 'trade_assets.was_drafted';

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
        self::TYPE_PHPNAME       => array('TradeassetId', 'TradeId', 'PlayerId', 'OldmanagerId', 'NewmanagerId', 'WasDrafted', ),
        self::TYPE_CAMELNAME     => array('tradeassetId', 'tradeId', 'playerId', 'oldmanagerId', 'newmanagerId', 'wasDrafted', ),
        self::TYPE_COLNAME       => array(TradeAssetsTableMap::COL_TRADEASSET_ID, TradeAssetsTableMap::COL_TRADE_ID, TradeAssetsTableMap::COL_PLAYER_ID, TradeAssetsTableMap::COL_OLDMANAGER_ID, TradeAssetsTableMap::COL_NEWMANAGER_ID, TradeAssetsTableMap::COL_WAS_DRAFTED, ),
        self::TYPE_FIELDNAME     => array('tradeasset_id', 'trade_id', 'player_id', 'oldmanager_id', 'newmanager_id', 'was_drafted', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('TradeassetId' => 0, 'TradeId' => 1, 'PlayerId' => 2, 'OldmanagerId' => 3, 'NewmanagerId' => 4, 'WasDrafted' => 5, ),
        self::TYPE_CAMELNAME     => array('tradeassetId' => 0, 'tradeId' => 1, 'playerId' => 2, 'oldmanagerId' => 3, 'newmanagerId' => 4, 'wasDrafted' => 5, ),
        self::TYPE_COLNAME       => array(TradeAssetsTableMap::COL_TRADEASSET_ID => 0, TradeAssetsTableMap::COL_TRADE_ID => 1, TradeAssetsTableMap::COL_PLAYER_ID => 2, TradeAssetsTableMap::COL_OLDMANAGER_ID => 3, TradeAssetsTableMap::COL_NEWMANAGER_ID => 4, TradeAssetsTableMap::COL_WAS_DRAFTED => 5, ),
        self::TYPE_FIELDNAME     => array('tradeasset_id' => 0, 'trade_id' => 1, 'player_id' => 2, 'oldmanager_id' => 3, 'newmanager_id' => 4, 'was_drafted' => 5, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, )
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
        $this->setName('trade_assets');
        $this->setPhpName('TradeAssets');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\TradeAssets');
        $this->setPackage('');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('tradeasset_id', 'TradeassetId', 'INTEGER', true, null, null);
        $this->addColumn('trade_id', 'TradeId', 'INTEGER', true, null, null);
        $this->addColumn('player_id', 'PlayerId', 'INTEGER', true, null, null);
        $this->addColumn('oldmanager_id', 'OldmanagerId', 'INTEGER', true, null, null);
        $this->addColumn('newmanager_id', 'NewmanagerId', 'INTEGER', true, null, null);
        $this->addColumn('was_drafted', 'WasDrafted', 'BOOLEAN', true, 1, null);
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('TradeassetId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('TradeassetId', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('TradeassetId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? TradeAssetsTableMap::CLASS_DEFAULT : TradeAssetsTableMap::OM_CLASS;
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
     * @return array           (TradeAssets object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = TradeAssetsTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = TradeAssetsTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + TradeAssetsTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = TradeAssetsTableMap::OM_CLASS;
            /** @var TradeAssets $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            TradeAssetsTableMap::addInstanceToPool($obj, $key);
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
            $key = TradeAssetsTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = TradeAssetsTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var TradeAssets $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                TradeAssetsTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(TradeAssetsTableMap::COL_TRADEASSET_ID);
            $criteria->addSelectColumn(TradeAssetsTableMap::COL_TRADE_ID);
            $criteria->addSelectColumn(TradeAssetsTableMap::COL_PLAYER_ID);
            $criteria->addSelectColumn(TradeAssetsTableMap::COL_OLDMANAGER_ID);
            $criteria->addSelectColumn(TradeAssetsTableMap::COL_NEWMANAGER_ID);
            $criteria->addSelectColumn(TradeAssetsTableMap::COL_WAS_DRAFTED);
        } else {
            $criteria->addSelectColumn($alias . '.tradeasset_id');
            $criteria->addSelectColumn($alias . '.trade_id');
            $criteria->addSelectColumn($alias . '.player_id');
            $criteria->addSelectColumn($alias . '.oldmanager_id');
            $criteria->addSelectColumn($alias . '.newmanager_id');
            $criteria->addSelectColumn($alias . '.was_drafted');
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
        return Propel::getServiceContainer()->getDatabaseMap(TradeAssetsTableMap::DATABASE_NAME)->getTable(TradeAssetsTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(TradeAssetsTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(TradeAssetsTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new TradeAssetsTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a TradeAssets or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or TradeAssets object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(TradeAssetsTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \TradeAssets) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(TradeAssetsTableMap::DATABASE_NAME);
            $criteria->add(TradeAssetsTableMap::COL_TRADEASSET_ID, (array) $values, Criteria::IN);
        }

        $query = TradeAssetsQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            TradeAssetsTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                TradeAssetsTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the trade_assets table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return TradeAssetsQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a TradeAssets or Criteria object.
     *
     * @param mixed               $criteria Criteria or TradeAssets object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(TradeAssetsTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from TradeAssets object
        }

        if ($criteria->containsKey(TradeAssetsTableMap::COL_TRADEASSET_ID) && $criteria->keyContainsValue(TradeAssetsTableMap::COL_TRADEASSET_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.TradeAssetsTableMap::COL_TRADEASSET_ID.')');
        }


        // Set the correct dbName
        $query = TradeAssetsQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // TradeAssetsTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
TradeAssetsTableMap::buildTableMap();
