<?php

namespace Map;

use \Draft;
use \DraftQuery;
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
 * This class defines the structure of the 'draft' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class DraftTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = '.Map.DraftTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'phpdraft';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'draft';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Draft';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Draft';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 13;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 13;

    /**
     * the column name for the draft_id field
     */
    const COL_DRAFT_ID = 'draft.draft_id';

    /**
     * the column name for the draft_create_time field
     */
    const COL_DRAFT_CREATE_TIME = 'draft.draft_create_time';

    /**
     * the column name for the draft_name field
     */
    const COL_DRAFT_NAME = 'draft.draft_name';

    /**
     * the column name for the draft_sport field
     */
    const COL_DRAFT_SPORT = 'draft.draft_sport';

    /**
     * the column name for the draft_status field
     */
    const COL_DRAFT_STATUS = 'draft.draft_status';

    /**
     * the column name for the draft_counter field
     */
    const COL_DRAFT_COUNTER = 'draft.draft_counter';

    /**
     * the column name for the draft_style field
     */
    const COL_DRAFT_STYLE = 'draft.draft_style';

    /**
     * the column name for the draft_rounds field
     */
    const COL_DRAFT_ROUNDS = 'draft.draft_rounds';

    /**
     * the column name for the draft_password field
     */
    const COL_DRAFT_PASSWORD = 'draft.draft_password';

    /**
     * the column name for the draft_start_time field
     */
    const COL_DRAFT_START_TIME = 'draft.draft_start_time';

    /**
     * the column name for the draft_end_time field
     */
    const COL_DRAFT_END_TIME = 'draft.draft_end_time';

    /**
     * the column name for the draft_current_round field
     */
    const COL_DRAFT_CURRENT_ROUND = 'draft.draft_current_round';

    /**
     * the column name for the draft_current_pick field
     */
    const COL_DRAFT_CURRENT_PICK = 'draft.draft_current_pick';

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
        self::TYPE_PHPNAME       => array('DraftId', 'DraftCreateTime', 'DraftName', 'DraftSport', 'DraftStatus', 'DraftCounter', 'DraftStyle', 'DraftRounds', 'DraftPassword', 'DraftStartTime', 'DraftEndTime', 'DraftCurrentRound', 'DraftCurrentPick', ),
        self::TYPE_CAMELNAME     => array('draftId', 'draftCreateTime', 'draftName', 'draftSport', 'draftStatus', 'draftCounter', 'draftStyle', 'draftRounds', 'draftPassword', 'draftStartTime', 'draftEndTime', 'draftCurrentRound', 'draftCurrentPick', ),
        self::TYPE_COLNAME       => array(DraftTableMap::COL_DRAFT_ID, DraftTableMap::COL_DRAFT_CREATE_TIME, DraftTableMap::COL_DRAFT_NAME, DraftTableMap::COL_DRAFT_SPORT, DraftTableMap::COL_DRAFT_STATUS, DraftTableMap::COL_DRAFT_COUNTER, DraftTableMap::COL_DRAFT_STYLE, DraftTableMap::COL_DRAFT_ROUNDS, DraftTableMap::COL_DRAFT_PASSWORD, DraftTableMap::COL_DRAFT_START_TIME, DraftTableMap::COL_DRAFT_END_TIME, DraftTableMap::COL_DRAFT_CURRENT_ROUND, DraftTableMap::COL_DRAFT_CURRENT_PICK, ),
        self::TYPE_FIELDNAME     => array('draft_id', 'draft_create_time', 'draft_name', 'draft_sport', 'draft_status', 'draft_counter', 'draft_style', 'draft_rounds', 'draft_password', 'draft_start_time', 'draft_end_time', 'draft_current_round', 'draft_current_pick', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('DraftId' => 0, 'DraftCreateTime' => 1, 'DraftName' => 2, 'DraftSport' => 3, 'DraftStatus' => 4, 'DraftCounter' => 5, 'DraftStyle' => 6, 'DraftRounds' => 7, 'DraftPassword' => 8, 'DraftStartTime' => 9, 'DraftEndTime' => 10, 'DraftCurrentRound' => 11, 'DraftCurrentPick' => 12, ),
        self::TYPE_CAMELNAME     => array('draftId' => 0, 'draftCreateTime' => 1, 'draftName' => 2, 'draftSport' => 3, 'draftStatus' => 4, 'draftCounter' => 5, 'draftStyle' => 6, 'draftRounds' => 7, 'draftPassword' => 8, 'draftStartTime' => 9, 'draftEndTime' => 10, 'draftCurrentRound' => 11, 'draftCurrentPick' => 12, ),
        self::TYPE_COLNAME       => array(DraftTableMap::COL_DRAFT_ID => 0, DraftTableMap::COL_DRAFT_CREATE_TIME => 1, DraftTableMap::COL_DRAFT_NAME => 2, DraftTableMap::COL_DRAFT_SPORT => 3, DraftTableMap::COL_DRAFT_STATUS => 4, DraftTableMap::COL_DRAFT_COUNTER => 5, DraftTableMap::COL_DRAFT_STYLE => 6, DraftTableMap::COL_DRAFT_ROUNDS => 7, DraftTableMap::COL_DRAFT_PASSWORD => 8, DraftTableMap::COL_DRAFT_START_TIME => 9, DraftTableMap::COL_DRAFT_END_TIME => 10, DraftTableMap::COL_DRAFT_CURRENT_ROUND => 11, DraftTableMap::COL_DRAFT_CURRENT_PICK => 12, ),
        self::TYPE_FIELDNAME     => array('draft_id' => 0, 'draft_create_time' => 1, 'draft_name' => 2, 'draft_sport' => 3, 'draft_status' => 4, 'draft_counter' => 5, 'draft_style' => 6, 'draft_rounds' => 7, 'draft_password' => 8, 'draft_start_time' => 9, 'draft_end_time' => 10, 'draft_current_round' => 11, 'draft_current_pick' => 12, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, )
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
        $this->setName('draft');
        $this->setPhpName('Draft');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\Draft');
        $this->setPackage('');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('draft_id', 'DraftId', 'INTEGER', true, null, null);
        $this->addColumn('draft_create_time', 'DraftCreateTime', 'TIMESTAMP', true, null, null);
        $this->addColumn('draft_name', 'DraftName', 'LONGVARCHAR', true, null, null);
        $this->addColumn('draft_sport', 'DraftSport', 'LONGVARCHAR', true, null, null);
        $this->addColumn('draft_status', 'DraftStatus', 'LONGVARCHAR', true, null, null);
        $this->addColumn('draft_counter', 'DraftCounter', 'INTEGER', true, null, 0);
        $this->addColumn('draft_style', 'DraftStyle', 'LONGVARCHAR', true, null, null);
        $this->addColumn('draft_rounds', 'DraftRounds', 'INTEGER', true, 2, 0);
        $this->addColumn('draft_password', 'DraftPassword', 'LONGVARCHAR', false, null, null);
        $this->addColumn('draft_start_time', 'DraftStartTime', 'TIMESTAMP', false, null, null);
        $this->addColumn('draft_end_time', 'DraftEndTime', 'TIMESTAMP', false, null, null);
        $this->addColumn('draft_current_round', 'DraftCurrentRound', 'INTEGER', true, 5, 1);
        $this->addColumn('draft_current_pick', 'DraftCurrentPick', 'INTEGER', true, 5, 1);
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('DraftId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('DraftId', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('DraftId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? DraftTableMap::CLASS_DEFAULT : DraftTableMap::OM_CLASS;
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
     * @return array           (Draft object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = DraftTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = DraftTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + DraftTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = DraftTableMap::OM_CLASS;
            /** @var Draft $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            DraftTableMap::addInstanceToPool($obj, $key);
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
            $key = DraftTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = DraftTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var Draft $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                DraftTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_ID);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_CREATE_TIME);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_NAME);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_SPORT);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_STATUS);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_COUNTER);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_STYLE);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_ROUNDS);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_PASSWORD);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_START_TIME);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_END_TIME);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_CURRENT_ROUND);
            $criteria->addSelectColumn(DraftTableMap::COL_DRAFT_CURRENT_PICK);
        } else {
            $criteria->addSelectColumn($alias . '.draft_id');
            $criteria->addSelectColumn($alias . '.draft_create_time');
            $criteria->addSelectColumn($alias . '.draft_name');
            $criteria->addSelectColumn($alias . '.draft_sport');
            $criteria->addSelectColumn($alias . '.draft_status');
            $criteria->addSelectColumn($alias . '.draft_counter');
            $criteria->addSelectColumn($alias . '.draft_style');
            $criteria->addSelectColumn($alias . '.draft_rounds');
            $criteria->addSelectColumn($alias . '.draft_password');
            $criteria->addSelectColumn($alias . '.draft_start_time');
            $criteria->addSelectColumn($alias . '.draft_end_time');
            $criteria->addSelectColumn($alias . '.draft_current_round');
            $criteria->addSelectColumn($alias . '.draft_current_pick');
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
        return Propel::getServiceContainer()->getDatabaseMap(DraftTableMap::DATABASE_NAME)->getTable(DraftTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(DraftTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(DraftTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new DraftTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a Draft or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or Draft object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(DraftTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Draft) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(DraftTableMap::DATABASE_NAME);
            $criteria->add(DraftTableMap::COL_DRAFT_ID, (array) $values, Criteria::IN);
        }

        $query = DraftQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            DraftTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                DraftTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the draft table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return DraftQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a Draft or Criteria object.
     *
     * @param mixed               $criteria Criteria or Draft object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(DraftTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from Draft object
        }

        if ($criteria->containsKey(DraftTableMap::COL_DRAFT_ID) && $criteria->keyContainsValue(DraftTableMap::COL_DRAFT_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.DraftTableMap::COL_DRAFT_ID.')');
        }


        // Set the correct dbName
        $query = DraftQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // DraftTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
DraftTableMap::buildTableMap();
