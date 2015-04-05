<?php

namespace Base;

use \PlayersQuery as ChildPlayersQuery;
use \DateTime;
use \Exception;
use \PDO;
use Map\PlayersTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;

/**
 * Base class that represents a row from the 'players' table.
 *
 *
 *
* @package    propel.generator..Base
*/
abstract class Players implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Map\\PlayersTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the player_id field.
     * @var        int
     */
    protected $player_id;

    /**
     * The value for the manager_id field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $manager_id;

    /**
     * The value for the first_name field.
     * @var        string
     */
    protected $first_name;

    /**
     * The value for the last_name field.
     * @var        string
     */
    protected $last_name;

    /**
     * The value for the team field.
     * @var        string
     */
    protected $team;

    /**
     * The value for the position field.
     * @var        string
     */
    protected $position;

    /**
     * The value for the pick_time field.
     * @var        \DateTime
     */
    protected $pick_time;

    /**
     * The value for the pick_duration field.
     * @var        int
     */
    protected $pick_duration;

    /**
     * The value for the player_counter field.
     * @var        int
     */
    protected $player_counter;

    /**
     * The value for the draft_id field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $draft_id;

    /**
     * The value for the player_round field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $player_round;

    /**
     * The value for the player_pick field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $player_pick;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->manager_id = 0;
        $this->draft_id = 0;
        $this->player_round = 0;
        $this->player_pick = 0;
    }

    /**
     * Initializes internal state of Base\Players object.
     * @see applyDefaults()
     */
    public function __construct()
    {
        $this->applyDefaultValues();
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>Players</code> instance.  If
     * <code>obj</code> is an instance of <code>Players</code>, delegates to
     * <code>equals(Players)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        if (!$obj instanceof static) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey() || null === $obj->getPrimaryKey()) {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return $this|Players The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [player_id] column value.
     *
     * @return int
     */
    public function getPlayerId()
    {
        return $this->player_id;
    }

    /**
     * Get the [manager_id] column value.
     *
     * @return int
     */
    public function getManagerId()
    {
        return $this->manager_id;
    }

    /**
     * Get the [first_name] column value.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Get the [last_name] column value.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Get the [team] column value.
     *
     * @return string
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Get the [position] column value.
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Get the [optionally formatted] temporal [pick_time] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getPickTime($format = NULL)
    {
        if ($format === null) {
            return $this->pick_time;
        } else {
            return $this->pick_time instanceof \DateTime ? $this->pick_time->format($format) : null;
        }
    }

    /**
     * Get the [pick_duration] column value.
     *
     * @return int
     */
    public function getPickDuration()
    {
        return $this->pick_duration;
    }

    /**
     * Get the [player_counter] column value.
     *
     * @return int
     */
    public function getPlayerCounter()
    {
        return $this->player_counter;
    }

    /**
     * Get the [draft_id] column value.
     *
     * @return int
     */
    public function getDraftId()
    {
        return $this->draft_id;
    }

    /**
     * Get the [player_round] column value.
     *
     * @return int
     */
    public function getPlayerRound()
    {
        return $this->player_round;
    }

    /**
     * Get the [player_pick] column value.
     *
     * @return int
     */
    public function getPlayerPick()
    {
        return $this->player_pick;
    }

    /**
     * Set the value of [player_id] column.
     *
     * @param int $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setPlayerId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->player_id !== $v) {
            $this->player_id = $v;
            $this->modifiedColumns[PlayersTableMap::COL_PLAYER_ID] = true;
        }

        return $this;
    } // setPlayerId()

    /**
     * Set the value of [manager_id] column.
     *
     * @param int $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setManagerId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->manager_id !== $v) {
            $this->manager_id = $v;
            $this->modifiedColumns[PlayersTableMap::COL_MANAGER_ID] = true;
        }

        return $this;
    } // setManagerId()

    /**
     * Set the value of [first_name] column.
     *
     * @param string $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setFirstName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->first_name !== $v) {
            $this->first_name = $v;
            $this->modifiedColumns[PlayersTableMap::COL_FIRST_NAME] = true;
        }

        return $this;
    } // setFirstName()

    /**
     * Set the value of [last_name] column.
     *
     * @param string $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setLastName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->last_name !== $v) {
            $this->last_name = $v;
            $this->modifiedColumns[PlayersTableMap::COL_LAST_NAME] = true;
        }

        return $this;
    } // setLastName()

    /**
     * Set the value of [team] column.
     *
     * @param string $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setTeam($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->team !== $v) {
            $this->team = $v;
            $this->modifiedColumns[PlayersTableMap::COL_TEAM] = true;
        }

        return $this;
    } // setTeam()

    /**
     * Set the value of [position] column.
     *
     * @param string $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setPosition($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->position !== $v) {
            $this->position = $v;
            $this->modifiedColumns[PlayersTableMap::COL_POSITION] = true;
        }

        return $this;
    } // setPosition()

    /**
     * Sets the value of [pick_time] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setPickTime($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->pick_time !== null || $dt !== null) {
            if ($this->pick_time === null || $dt === null || $dt->format("Y-m-d H:i:s") !== $this->pick_time->format("Y-m-d H:i:s")) {
                $this->pick_time = $dt === null ? null : clone $dt;
                $this->modifiedColumns[PlayersTableMap::COL_PICK_TIME] = true;
            }
        } // if either are not null

        return $this;
    } // setPickTime()

    /**
     * Set the value of [pick_duration] column.
     *
     * @param int $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setPickDuration($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->pick_duration !== $v) {
            $this->pick_duration = $v;
            $this->modifiedColumns[PlayersTableMap::COL_PICK_DURATION] = true;
        }

        return $this;
    } // setPickDuration()

    /**
     * Set the value of [player_counter] column.
     *
     * @param int $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setPlayerCounter($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->player_counter !== $v) {
            $this->player_counter = $v;
            $this->modifiedColumns[PlayersTableMap::COL_PLAYER_COUNTER] = true;
        }

        return $this;
    } // setPlayerCounter()

    /**
     * Set the value of [draft_id] column.
     *
     * @param int $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setDraftId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->draft_id !== $v) {
            $this->draft_id = $v;
            $this->modifiedColumns[PlayersTableMap::COL_DRAFT_ID] = true;
        }

        return $this;
    } // setDraftId()

    /**
     * Set the value of [player_round] column.
     *
     * @param int $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setPlayerRound($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->player_round !== $v) {
            $this->player_round = $v;
            $this->modifiedColumns[PlayersTableMap::COL_PLAYER_ROUND] = true;
        }

        return $this;
    } // setPlayerRound()

    /**
     * Set the value of [player_pick] column.
     *
     * @param int $v new value
     * @return $this|\Players The current object (for fluent API support)
     */
    public function setPlayerPick($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->player_pick !== $v) {
            $this->player_pick = $v;
            $this->modifiedColumns[PlayersTableMap::COL_PLAYER_PICK] = true;
        }

        return $this;
    } // setPlayerPick()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
            if ($this->manager_id !== 0) {
                return false;
            }

            if ($this->draft_id !== 0) {
                return false;
            }

            if ($this->player_round !== 0) {
                return false;
            }

            if ($this->player_pick !== 0) {
                return false;
            }

        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : PlayersTableMap::translateFieldName('PlayerId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->player_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : PlayersTableMap::translateFieldName('ManagerId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->manager_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : PlayersTableMap::translateFieldName('FirstName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->first_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : PlayersTableMap::translateFieldName('LastName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->last_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : PlayersTableMap::translateFieldName('Team', TableMap::TYPE_PHPNAME, $indexType)];
            $this->team = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : PlayersTableMap::translateFieldName('Position', TableMap::TYPE_PHPNAME, $indexType)];
            $this->position = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : PlayersTableMap::translateFieldName('PickTime', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->pick_time = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : PlayersTableMap::translateFieldName('PickDuration', TableMap::TYPE_PHPNAME, $indexType)];
            $this->pick_duration = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : PlayersTableMap::translateFieldName('PlayerCounter', TableMap::TYPE_PHPNAME, $indexType)];
            $this->player_counter = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : PlayersTableMap::translateFieldName('DraftId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : PlayersTableMap::translateFieldName('PlayerRound', TableMap::TYPE_PHPNAME, $indexType)];
            $this->player_round = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 11 + $startcol : PlayersTableMap::translateFieldName('PlayerPick', TableMap::TYPE_PHPNAME, $indexType)];
            $this->player_pick = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 12; // 12 = PlayersTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Players'), 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(PlayersTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildPlayersQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Players::setDeleted()
     * @see Players::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(PlayersTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildPlayersQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $this->setDeleted(true);
            }
        });
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(PlayersTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $isInsert = $this->isNew();
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                PlayersTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }

            return $affectedRows;
        });
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                    $affectedRows += 1;
                } else {
                    $affectedRows += $this->doUpdate($con);
                }
                $this->resetModified();
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[PlayersTableMap::COL_PLAYER_ID] = true;
        if (null !== $this->player_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . PlayersTableMap::COL_PLAYER_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(PlayersTableMap::COL_PLAYER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'player_id';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_MANAGER_ID)) {
            $modifiedColumns[':p' . $index++]  = 'manager_id';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_FIRST_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'first_name';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_LAST_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'last_name';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_TEAM)) {
            $modifiedColumns[':p' . $index++]  = 'team';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_POSITION)) {
            $modifiedColumns[':p' . $index++]  = 'position';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PICK_TIME)) {
            $modifiedColumns[':p' . $index++]  = 'pick_time';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PICK_DURATION)) {
            $modifiedColumns[':p' . $index++]  = 'pick_duration';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PLAYER_COUNTER)) {
            $modifiedColumns[':p' . $index++]  = 'player_counter';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_DRAFT_ID)) {
            $modifiedColumns[':p' . $index++]  = 'draft_id';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PLAYER_ROUND)) {
            $modifiedColumns[':p' . $index++]  = 'player_round';
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PLAYER_PICK)) {
            $modifiedColumns[':p' . $index++]  = 'player_pick';
        }

        $sql = sprintf(
            'INSERT INTO players (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'player_id':
                        $stmt->bindValue($identifier, $this->player_id, PDO::PARAM_INT);
                        break;
                    case 'manager_id':
                        $stmt->bindValue($identifier, $this->manager_id, PDO::PARAM_INT);
                        break;
                    case 'first_name':
                        $stmt->bindValue($identifier, $this->first_name, PDO::PARAM_STR);
                        break;
                    case 'last_name':
                        $stmt->bindValue($identifier, $this->last_name, PDO::PARAM_STR);
                        break;
                    case 'team':
                        $stmt->bindValue($identifier, $this->team, PDO::PARAM_STR);
                        break;
                    case 'position':
                        $stmt->bindValue($identifier, $this->position, PDO::PARAM_STR);
                        break;
                    case 'pick_time':
                        $stmt->bindValue($identifier, $this->pick_time ? $this->pick_time->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'pick_duration':
                        $stmt->bindValue($identifier, $this->pick_duration, PDO::PARAM_INT);
                        break;
                    case 'player_counter':
                        $stmt->bindValue($identifier, $this->player_counter, PDO::PARAM_INT);
                        break;
                    case 'draft_id':
                        $stmt->bindValue($identifier, $this->draft_id, PDO::PARAM_INT);
                        break;
                    case 'player_round':
                        $stmt->bindValue($identifier, $this->player_round, PDO::PARAM_INT);
                        break;
                    case 'player_pick':
                        $stmt->bindValue($identifier, $this->player_pick, PDO::PARAM_INT);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setPlayerId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = PlayersTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getPlayerId();
                break;
            case 1:
                return $this->getManagerId();
                break;
            case 2:
                return $this->getFirstName();
                break;
            case 3:
                return $this->getLastName();
                break;
            case 4:
                return $this->getTeam();
                break;
            case 5:
                return $this->getPosition();
                break;
            case 6:
                return $this->getPickTime();
                break;
            case 7:
                return $this->getPickDuration();
                break;
            case 8:
                return $this->getPlayerCounter();
                break;
            case 9:
                return $this->getDraftId();
                break;
            case 10:
                return $this->getPlayerRound();
                break;
            case 11:
                return $this->getPlayerPick();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array())
    {

        if (isset($alreadyDumpedObjects['Players'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Players'][$this->hashCode()] = true;
        $keys = PlayersTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getPlayerId(),
            $keys[1] => $this->getManagerId(),
            $keys[2] => $this->getFirstName(),
            $keys[3] => $this->getLastName(),
            $keys[4] => $this->getTeam(),
            $keys[5] => $this->getPosition(),
            $keys[6] => $this->getPickTime(),
            $keys[7] => $this->getPickDuration(),
            $keys[8] => $this->getPlayerCounter(),
            $keys[9] => $this->getDraftId(),
            $keys[10] => $this->getPlayerRound(),
            $keys[11] => $this->getPlayerPick(),
        );

        $utc = new \DateTimeZone('utc');
        if ($result[$keys[6]] instanceof \DateTime) {
            // When changing timezone we don't want to change existing instances
            $dateTime = clone $result[$keys[6]];
            $result[$keys[6]] = $dateTime->setTimezone($utc)->format('Y-m-d\TH:i:s\Z');
        }

        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }


        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param  string $name
     * @param  mixed  $value field value
     * @param  string $type The type of fieldname the $name is of:
     *                one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                Defaults to TableMap::TYPE_PHPNAME.
     * @return $this|\Players
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = PlayersTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Players
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setPlayerId($value);
                break;
            case 1:
                $this->setManagerId($value);
                break;
            case 2:
                $this->setFirstName($value);
                break;
            case 3:
                $this->setLastName($value);
                break;
            case 4:
                $this->setTeam($value);
                break;
            case 5:
                $this->setPosition($value);
                break;
            case 6:
                $this->setPickTime($value);
                break;
            case 7:
                $this->setPickDuration($value);
                break;
            case 8:
                $this->setPlayerCounter($value);
                break;
            case 9:
                $this->setDraftId($value);
                break;
            case 10:
                $this->setPlayerRound($value);
                break;
            case 11:
                $this->setPlayerPick($value);
                break;
        } // switch()

        return $this;
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = PlayersTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setPlayerId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setManagerId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setFirstName($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setLastName($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setTeam($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setPosition($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setPickTime($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setPickDuration($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setPlayerCounter($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setDraftId($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setPlayerRound($arr[$keys[10]]);
        }
        if (array_key_exists($keys[11], $arr)) {
            $this->setPlayerPick($arr[$keys[11]]);
        }
    }

     /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     * @param string $keyType The type of keys the array uses.
     *
     * @return $this|\Players The current object, for fluid interface
     */
    public function importFrom($parser, $data, $keyType = TableMap::TYPE_PHPNAME)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), $keyType);

        return $this;
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(PlayersTableMap::DATABASE_NAME);

        if ($this->isColumnModified(PlayersTableMap::COL_PLAYER_ID)) {
            $criteria->add(PlayersTableMap::COL_PLAYER_ID, $this->player_id);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_MANAGER_ID)) {
            $criteria->add(PlayersTableMap::COL_MANAGER_ID, $this->manager_id);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_FIRST_NAME)) {
            $criteria->add(PlayersTableMap::COL_FIRST_NAME, $this->first_name);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_LAST_NAME)) {
            $criteria->add(PlayersTableMap::COL_LAST_NAME, $this->last_name);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_TEAM)) {
            $criteria->add(PlayersTableMap::COL_TEAM, $this->team);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_POSITION)) {
            $criteria->add(PlayersTableMap::COL_POSITION, $this->position);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PICK_TIME)) {
            $criteria->add(PlayersTableMap::COL_PICK_TIME, $this->pick_time);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PICK_DURATION)) {
            $criteria->add(PlayersTableMap::COL_PICK_DURATION, $this->pick_duration);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PLAYER_COUNTER)) {
            $criteria->add(PlayersTableMap::COL_PLAYER_COUNTER, $this->player_counter);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_DRAFT_ID)) {
            $criteria->add(PlayersTableMap::COL_DRAFT_ID, $this->draft_id);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PLAYER_ROUND)) {
            $criteria->add(PlayersTableMap::COL_PLAYER_ROUND, $this->player_round);
        }
        if ($this->isColumnModified(PlayersTableMap::COL_PLAYER_PICK)) {
            $criteria->add(PlayersTableMap::COL_PLAYER_PICK, $this->player_pick);
        }

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @throws LogicException if no primary key is defined
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = ChildPlayersQuery::create();
        $criteria->add(PlayersTableMap::COL_PLAYER_ID, $this->player_id);

        return $criteria;
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        $validPk = null !== $this->getPlayerId();

        $validPrimaryKeyFKs = 0;
        $primaryKeyFKs = [];

        if ($validPk) {
            return crc32(json_encode($this->getPrimaryKey(), JSON_UNESCAPED_UNICODE));
        } elseif ($validPrimaryKeyFKs) {
            return crc32(json_encode($primaryKeyFKs, JSON_UNESCAPED_UNICODE));
        }

        return spl_object_hash($this);
    }

    /**
     * Returns the primary key for this object (row).
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getPlayerId();
    }

    /**
     * Generic method to set the primary key (player_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setPlayerId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getPlayerId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Players (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setManagerId($this->getManagerId());
        $copyObj->setFirstName($this->getFirstName());
        $copyObj->setLastName($this->getLastName());
        $copyObj->setTeam($this->getTeam());
        $copyObj->setPosition($this->getPosition());
        $copyObj->setPickTime($this->getPickTime());
        $copyObj->setPickDuration($this->getPickDuration());
        $copyObj->setPlayerCounter($this->getPlayerCounter());
        $copyObj->setDraftId($this->getDraftId());
        $copyObj->setPlayerRound($this->getPlayerRound());
        $copyObj->setPlayerPick($this->getPlayerPick());
        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setPlayerId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param  boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return \Players Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        $this->player_id = null;
        $this->manager_id = null;
        $this->first_name = null;
        $this->last_name = null;
        $this->team = null;
        $this->position = null;
        $this->pick_time = null;
        $this->pick_duration = null;
        $this->player_counter = null;
        $this->draft_id = null;
        $this->player_round = null;
        $this->player_pick = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references and back-references to other model objects or collections of model objects.
     *
     * This method is used to reset all php object references (not the actual reference in the database).
     * Necessary for object serialisation.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
        } // if ($deep)

    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(PlayersTableMap::DEFAULT_STRING_FORMAT);
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
