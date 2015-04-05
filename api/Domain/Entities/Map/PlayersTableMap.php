<?php

namespace Map;

use \Players;
use \PlayersQuery;
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
 * This class defines the structure of the 'players' table.
 *
 *
 *
 * This map class is used by Propel to do runtime db structure discovery.
 * For example, the createSelectSql() method checks the type of a given column used in an
 * ORDER BY clause to know whether it needs to apply SQL to make the ORDER BY case-insensitive
 * (i.e. if it's a text column type).
 *
 */
class PlayersTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = '.Map.PlayersTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'phpdraft';

    /**
     * The table name for this class
     */
    const TABLE_NAME = 'players';

    /**
     * The related Propel class for this table
     */
    const OM_CLASS = '\\Players';

    /**
     * A class that can be returned by this tableMap
     */
    const CLASS_DEFAULT = 'Players';

    /**
     * The total number of columns
     */
    const NUM_COLUMNS = 12;

    /**
     * The number of lazy-loaded columns
     */
    const NUM_LAZY_LOAD_COLUMNS = 0;

    /**
     * The number of columns to hydrate (NUM_COLUMNS - NUM_LAZY_LOAD_COLUMNS)
     */
    const NUM_HYDRATE_COLUMNS = 12;

    /**
     * the column name for the player_id field
     */
    const COL_PLAYER_ID = 'players.player_id';

    /**
     * the column name for the manager_id field
     */
    const COL_MANAGER_ID = 'players.manager_id';

    /**
     * the column name for the first_name field
     */
    const COL_FIRST_NAME = 'players.first_name';

    /**
     * the column name for the last_name field
     */
    const COL_LAST_NAME = 'players.last_name';

    /**
     * the column name for the team field
     */
    const COL_TEAM = 'players.team';

    /**
     * the column name for the position field
     */
    const COL_POSITION = 'players.position';

    /**
     * the column name for the pick_time field
     */
    const COL_PICK_TIME = 'players.pick_time';

    /**
     * the column name for the pick_duration field
     */
    const COL_PICK_DURATION = 'players.pick_duration';

    /**
     * the column name for the player_counter field
     */
    const COL_PLAYER_COUNTER = 'players.player_counter';

    /**
     * the column name for the draft_id field
     */
    const COL_DRAFT_ID = 'players.draft_id';

    /**
     * the column name for the player_round field
     */
    const COL_PLAYER_ROUND = 'players.player_round';

    /**
     * the column name for the player_pick field
     */
    const COL_PLAYER_PICK = 'players.player_pick';

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
        self::TYPE_PHPNAME       => array('PlayerId', 'ManagerId', 'FirstName', 'LastName', 'Team', 'Position', 'PickTime', 'PickDuration', 'PlayerCounter', 'DraftId', 'PlayerRound', 'PlayerPick', ),
        self::TYPE_CAMELNAME     => array('playerId', 'managerId', 'firstName', 'lastName', 'team', 'position', 'pickTime', 'pickDuration', 'playerCounter', 'draftId', 'playerRound', 'playerPick', ),
        self::TYPE_COLNAME       => array(PlayersTableMap::COL_PLAYER_ID, PlayersTableMap::COL_MANAGER_ID, PlayersTableMap::COL_FIRST_NAME, PlayersTableMap::COL_LAST_NAME, PlayersTableMap::COL_TEAM, PlayersTableMap::COL_POSITION, PlayersTableMap::COL_PICK_TIME, PlayersTableMap::COL_PICK_DURATION, PlayersTableMap::COL_PLAYER_COUNTER, PlayersTableMap::COL_DRAFT_ID, PlayersTableMap::COL_PLAYER_ROUND, PlayersTableMap::COL_PLAYER_PICK, ),
        self::TYPE_FIELDNAME     => array('player_id', 'manager_id', 'first_name', 'last_name', 'team', 'position', 'pick_time', 'pick_duration', 'player_counter', 'draft_id', 'player_round', 'player_pick', ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, )
    );

    /**
     * holds an array of keys for quick access to the fieldnames array
     *
     * first dimension keys are the type constants
     * e.g. self::$fieldKeys[self::TYPE_PHPNAME]['Id'] = 0
     */
    protected static $fieldKeys = array (
        self::TYPE_PHPNAME       => array('PlayerId' => 0, 'ManagerId' => 1, 'FirstName' => 2, 'LastName' => 3, 'Team' => 4, 'Position' => 5, 'PickTime' => 6, 'PickDuration' => 7, 'PlayerCounter' => 8, 'DraftId' => 9, 'PlayerRound' => 10, 'PlayerPick' => 11, ),
        self::TYPE_CAMELNAME     => array('playerId' => 0, 'managerId' => 1, 'firstName' => 2, 'lastName' => 3, 'team' => 4, 'position' => 5, 'pickTime' => 6, 'pickDuration' => 7, 'playerCounter' => 8, 'draftId' => 9, 'playerRound' => 10, 'playerPick' => 11, ),
        self::TYPE_COLNAME       => array(PlayersTableMap::COL_PLAYER_ID => 0, PlayersTableMap::COL_MANAGER_ID => 1, PlayersTableMap::COL_FIRST_NAME => 2, PlayersTableMap::COL_LAST_NAME => 3, PlayersTableMap::COL_TEAM => 4, PlayersTableMap::COL_POSITION => 5, PlayersTableMap::COL_PICK_TIME => 6, PlayersTableMap::COL_PICK_DURATION => 7, PlayersTableMap::COL_PLAYER_COUNTER => 8, PlayersTableMap::COL_DRAFT_ID => 9, PlayersTableMap::COL_PLAYER_ROUND => 10, PlayersTableMap::COL_PLAYER_PICK => 11, ),
        self::TYPE_FIELDNAME     => array('player_id' => 0, 'manager_id' => 1, 'first_name' => 2, 'last_name' => 3, 'team' => 4, 'position' => 5, 'pick_time' => 6, 'pick_duration' => 7, 'player_counter' => 8, 'draft_id' => 9, 'player_round' => 10, 'player_pick' => 11, ),
        self::TYPE_NUM           => array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, )
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
        $this->setName('players');
        $this->setPhpName('Players');
        $this->setIdentifierQuoting(false);
        $this->setClassName('\\Players');
        $this->setPackage('');
        $this->setUseIdGenerator(true);
        // columns
        $this->addPrimaryKey('player_id', 'PlayerId', 'INTEGER', true, null, null);
        $this->addColumn('manager_id', 'ManagerId', 'INTEGER', true, null, 0);
        $this->addColumn('first_name', 'FirstName', 'LONGVARCHAR', false, null, null);
        $this->addColumn('last_name', 'LastName', 'LONGVARCHAR', false, null, null);
        $this->addColumn('team', 'Team', 'CHAR', false, 3, null);
        $this->addColumn('position', 'Position', 'VARCHAR', false, 4, null);
        $this->addColumn('pick_time', 'PickTime', 'TIMESTAMP', false, null, null);
        $this->addColumn('pick_duration', 'PickDuration', 'INTEGER', false, 10, null);
        $this->addColumn('player_counter', 'PlayerCounter', 'INTEGER', false, null, null);
        $this->addColumn('draft_id', 'DraftId', 'INTEGER', true, null, 0);
        $this->addColumn('player_round', 'PlayerRound', 'INTEGER', true, null, 0);
        $this->addColumn('player_pick', 'PlayerPick', 'INTEGER', true, null, 0);
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
        if ($row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('PlayerId', TableMap::TYPE_PHPNAME, $indexType)] === null) {
            return null;
        }

        return (string) $row[TableMap::TYPE_NUM == $indexType ? 0 + $offset : static::translateFieldName('PlayerId', TableMap::TYPE_PHPNAME, $indexType)];
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
                : self::translateFieldName('PlayerId', TableMap::TYPE_PHPNAME, $indexType)
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
        return $withPrefix ? PlayersTableMap::CLASS_DEFAULT : PlayersTableMap::OM_CLASS;
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
     * @return array           (Players object, last column rank)
     */
    public static function populateObject($row, $offset = 0, $indexType = TableMap::TYPE_NUM)
    {
        $key = PlayersTableMap::getPrimaryKeyHashFromRow($row, $offset, $indexType);
        if (null !== ($obj = PlayersTableMap::getInstanceFromPool($key))) {
            // We no longer rehydrate the object, since this can cause data loss.
            // See http://www.propelorm.org/ticket/509
            // $obj->hydrate($row, $offset, true); // rehydrate
            $col = $offset + PlayersTableMap::NUM_HYDRATE_COLUMNS;
        } else {
            $cls = PlayersTableMap::OM_CLASS;
            /** @var Players $obj */
            $obj = new $cls();
            $col = $obj->hydrate($row, $offset, false, $indexType);
            PlayersTableMap::addInstanceToPool($obj, $key);
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
            $key = PlayersTableMap::getPrimaryKeyHashFromRow($row, 0, $dataFetcher->getIndexType());
            if (null !== ($obj = PlayersTableMap::getInstanceFromPool($key))) {
                // We no longer rehydrate the object, since this can cause data loss.
                // See http://www.propelorm.org/ticket/509
                // $obj->hydrate($row, 0, true); // rehydrate
                $results[] = $obj;
            } else {
                /** @var Players $obj */
                $obj = new $cls();
                $obj->hydrate($row);
                $results[] = $obj;
                PlayersTableMap::addInstanceToPool($obj, $key);
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
            $criteria->addSelectColumn(PlayersTableMap::COL_PLAYER_ID);
            $criteria->addSelectColumn(PlayersTableMap::COL_MANAGER_ID);
            $criteria->addSelectColumn(PlayersTableMap::COL_FIRST_NAME);
            $criteria->addSelectColumn(PlayersTableMap::COL_LAST_NAME);
            $criteria->addSelectColumn(PlayersTableMap::COL_TEAM);
            $criteria->addSelectColumn(PlayersTableMap::COL_POSITION);
            $criteria->addSelectColumn(PlayersTableMap::COL_PICK_TIME);
            $criteria->addSelectColumn(PlayersTableMap::COL_PICK_DURATION);
            $criteria->addSelectColumn(PlayersTableMap::COL_PLAYER_COUNTER);
            $criteria->addSelectColumn(PlayersTableMap::COL_DRAFT_ID);
            $criteria->addSelectColumn(PlayersTableMap::COL_PLAYER_ROUND);
            $criteria->addSelectColumn(PlayersTableMap::COL_PLAYER_PICK);
        } else {
            $criteria->addSelectColumn($alias . '.player_id');
            $criteria->addSelectColumn($alias . '.manager_id');
            $criteria->addSelectColumn($alias . '.first_name');
            $criteria->addSelectColumn($alias . '.last_name');
            $criteria->addSelectColumn($alias . '.team');
            $criteria->addSelectColumn($alias . '.position');
            $criteria->addSelectColumn($alias . '.pick_time');
            $criteria->addSelectColumn($alias . '.pick_duration');
            $criteria->addSelectColumn($alias . '.player_counter');
            $criteria->addSelectColumn($alias . '.draft_id');
            $criteria->addSelectColumn($alias . '.player_round');
            $criteria->addSelectColumn($alias . '.player_pick');
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
        return Propel::getServiceContainer()->getDatabaseMap(PlayersTableMap::DATABASE_NAME)->getTable(PlayersTableMap::TABLE_NAME);
    }

    /**
     * Add a TableMap instance to the database for this tableMap class.
     */
    public static function buildTableMap()
    {
        $dbMap = Propel::getServiceContainer()->getDatabaseMap(PlayersTableMap::DATABASE_NAME);
        if (!$dbMap->hasTable(PlayersTableMap::TABLE_NAME)) {
            $dbMap->addTableObject(new PlayersTableMap());
        }
    }

    /**
     * Performs a DELETE on the database, given a Players or Criteria object OR a primary key value.
     *
     * @param mixed               $values Criteria or Players object or primary key or array of primary keys
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
            $con = Propel::getServiceContainer()->getWriteConnection(PlayersTableMap::DATABASE_NAME);
        }

        if ($values instanceof Criteria) {
            // rename for clarity
            $criteria = $values;
        } elseif ($values instanceof \Players) { // it's a model object
            // create criteria based on pk values
            $criteria = $values->buildPkeyCriteria();
        } else { // it's a primary key, or an array of pks
            $criteria = new Criteria(PlayersTableMap::DATABASE_NAME);
            $criteria->add(PlayersTableMap::COL_PLAYER_ID, (array) $values, Criteria::IN);
        }

        $query = PlayersQuery::create()->mergeWith($criteria);

        if ($values instanceof Criteria) {
            PlayersTableMap::clearInstancePool();
        } elseif (!is_object($values)) { // it's a primary key, or an array of pks
            foreach ((array) $values as $singleval) {
                PlayersTableMap::removeInstanceFromPool($singleval);
            }
        }

        return $query->delete($con);
    }

    /**
     * Deletes all rows from the players table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public static function doDeleteAll(ConnectionInterface $con = null)
    {
        return PlayersQuery::create()->doDeleteAll($con);
    }

    /**
     * Performs an INSERT on the database, given a Players or Criteria object.
     *
     * @param mixed               $criteria Criteria or Players object containing data that is used to create the INSERT statement.
     * @param ConnectionInterface $con the ConnectionInterface connection to use
     * @return mixed           The new primary key.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public static function doInsert($criteria, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(PlayersTableMap::DATABASE_NAME);
        }

        if ($criteria instanceof Criteria) {
            $criteria = clone $criteria; // rename for clarity
        } else {
            $criteria = $criteria->buildCriteria(); // build Criteria from Players object
        }

        if ($criteria->containsKey(PlayersTableMap::COL_PLAYER_ID) && $criteria->keyContainsValue(PlayersTableMap::COL_PLAYER_ID) ) {
            throw new PropelException('Cannot insert a value for auto-increment primary key ('.PlayersTableMap::COL_PLAYER_ID.')');
        }


        // Set the correct dbName
        $query = PlayersQuery::create()->mergeWith($criteria);

        // use transaction because $criteria could contain info
        // for more than one table (I guess, conceivably)
        return $con->transaction(function () use ($con, $query) {
            return $query->doInsert($con);
        });
    }

} // PlayersTableMap
// This is the static code needed to register the TableMap for this table with the main Propel class.
//
PlayersTableMap::buildTableMap();
