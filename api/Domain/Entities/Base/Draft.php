<?php

namespace Base;

use \DraftQuery as ChildDraftQuery;
use \DateTime;
use \Exception;
use \PDO;
use Map\DraftTableMap;
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
 * Base class that represents a row from the 'draft' table.
 *
 *
 *
* @package    propel.generator..Base
*/
abstract class Draft implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\Map\\DraftTableMap';


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
     * The value for the draft_id field.
     * @var        int
     */
    protected $draft_id;

    /**
     * The value for the draft_create_time field.
     * @var        \DateTime
     */
    protected $draft_create_time;

    /**
     * The value for the draft_name field.
     * @var        string
     */
    protected $draft_name;

    /**
     * The value for the draft_sport field.
     * @var        string
     */
    protected $draft_sport;

    /**
     * The value for the draft_status field.
     * @var        string
     */
    protected $draft_status;

    /**
     * The value for the draft_counter field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $draft_counter;

    /**
     * The value for the draft_style field.
     * @var        string
     */
    protected $draft_style;

    /**
     * The value for the draft_rounds field.
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $draft_rounds;

    /**
     * The value for the draft_password field.
     * @var        string
     */
    protected $draft_password;

    /**
     * The value for the draft_start_time field.
     * @var        \DateTime
     */
    protected $draft_start_time;

    /**
     * The value for the draft_end_time field.
     * @var        \DateTime
     */
    protected $draft_end_time;

    /**
     * The value for the draft_current_round field.
     * Note: this column has a database default value of: 1
     * @var        int
     */
    protected $draft_current_round;

    /**
     * The value for the draft_current_pick field.
     * Note: this column has a database default value of: 1
     * @var        int
     */
    protected $draft_current_pick;

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
        $this->draft_counter = 0;
        $this->draft_rounds = 0;
        $this->draft_current_round = 1;
        $this->draft_current_pick = 1;
    }

    /**
     * Initializes internal state of Base\Draft object.
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
     * Compares this with another <code>Draft</code> instance.  If
     * <code>obj</code> is an instance of <code>Draft</code>, delegates to
     * <code>equals(Draft)</code>.  Otherwise, returns <code>false</code>.
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
     * @return $this|Draft The current object, for fluid interface
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
     * Get the [draft_id] column value.
     *
     * @return int
     */
    public function getDraftId()
    {
        return $this->draft_id;
    }

    /**
     * Get the [optionally formatted] temporal [draft_create_time] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getDraftCreateTime($format = NULL)
    {
        if ($format === null) {
            return $this->draft_create_time;
        } else {
            return $this->draft_create_time instanceof \DateTime ? $this->draft_create_time->format($format) : null;
        }
    }

    /**
     * Get the [draft_name] column value.
     *
     * @return string
     */
    public function getDraftName()
    {
        return $this->draft_name;
    }

    /**
     * Get the [draft_sport] column value.
     *
     * @return string
     */
    public function getDraftSport()
    {
        return $this->draft_sport;
    }

    /**
     * Get the [draft_status] column value.
     *
     * @return string
     */
    public function getDraftStatus()
    {
        return $this->draft_status;
    }

    /**
     * Get the [draft_counter] column value.
     *
     * @return int
     */
    public function getDraftCounter()
    {
        return $this->draft_counter;
    }

    /**
     * Get the [draft_style] column value.
     *
     * @return string
     */
    public function getDraftStyle()
    {
        return $this->draft_style;
    }

    /**
     * Get the [draft_rounds] column value.
     *
     * @return int
     */
    public function getDraftRounds()
    {
        return $this->draft_rounds;
    }

    /**
     * Get the [draft_password] column value.
     *
     * @return string
     */
    public function getDraftPassword()
    {
        return $this->draft_password;
    }

    /**
     * Get the [optionally formatted] temporal [draft_start_time] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getDraftStartTime($format = NULL)
    {
        if ($format === null) {
            return $this->draft_start_time;
        } else {
            return $this->draft_start_time instanceof \DateTime ? $this->draft_start_time->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [draft_end_time] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw DateTime object will be returned.
     *
     * @return string|DateTime Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getDraftEndTime($format = NULL)
    {
        if ($format === null) {
            return $this->draft_end_time;
        } else {
            return $this->draft_end_time instanceof \DateTime ? $this->draft_end_time->format($format) : null;
        }
    }

    /**
     * Get the [draft_current_round] column value.
     *
     * @return int
     */
    public function getDraftCurrentRound()
    {
        return $this->draft_current_round;
    }

    /**
     * Get the [draft_current_pick] column value.
     *
     * @return int
     */
    public function getDraftCurrentPick()
    {
        return $this->draft_current_pick;
    }

    /**
     * Set the value of [draft_id] column.
     *
     * @param int $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->draft_id !== $v) {
            $this->draft_id = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_ID] = true;
        }

        return $this;
    } // setDraftId()

    /**
     * Sets the value of [draft_create_time] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftCreateTime($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->draft_create_time !== null || $dt !== null) {
            if ($this->draft_create_time === null || $dt === null || $dt->format("Y-m-d H:i:s") !== $this->draft_create_time->format("Y-m-d H:i:s")) {
                $this->draft_create_time = $dt === null ? null : clone $dt;
                $this->modifiedColumns[DraftTableMap::COL_DRAFT_CREATE_TIME] = true;
            }
        } // if either are not null

        return $this;
    } // setDraftCreateTime()

    /**
     * Set the value of [draft_name] column.
     *
     * @param string $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->draft_name !== $v) {
            $this->draft_name = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_NAME] = true;
        }

        return $this;
    } // setDraftName()

    /**
     * Set the value of [draft_sport] column.
     *
     * @param string $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftSport($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->draft_sport !== $v) {
            $this->draft_sport = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_SPORT] = true;
        }

        return $this;
    } // setDraftSport()

    /**
     * Set the value of [draft_status] column.
     *
     * @param string $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftStatus($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->draft_status !== $v) {
            $this->draft_status = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_STATUS] = true;
        }

        return $this;
    } // setDraftStatus()

    /**
     * Set the value of [draft_counter] column.
     *
     * @param int $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftCounter($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->draft_counter !== $v) {
            $this->draft_counter = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_COUNTER] = true;
        }

        return $this;
    } // setDraftCounter()

    /**
     * Set the value of [draft_style] column.
     *
     * @param string $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftStyle($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->draft_style !== $v) {
            $this->draft_style = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_STYLE] = true;
        }

        return $this;
    } // setDraftStyle()

    /**
     * Set the value of [draft_rounds] column.
     *
     * @param int $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftRounds($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->draft_rounds !== $v) {
            $this->draft_rounds = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_ROUNDS] = true;
        }

        return $this;
    } // setDraftRounds()

    /**
     * Set the value of [draft_password] column.
     *
     * @param string $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftPassword($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->draft_password !== $v) {
            $this->draft_password = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_PASSWORD] = true;
        }

        return $this;
    } // setDraftPassword()

    /**
     * Sets the value of [draft_start_time] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftStartTime($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->draft_start_time !== null || $dt !== null) {
            if ($this->draft_start_time === null || $dt === null || $dt->format("Y-m-d H:i:s") !== $this->draft_start_time->format("Y-m-d H:i:s")) {
                $this->draft_start_time = $dt === null ? null : clone $dt;
                $this->modifiedColumns[DraftTableMap::COL_DRAFT_START_TIME] = true;
            }
        } // if either are not null

        return $this;
    } // setDraftStartTime()

    /**
     * Sets the value of [draft_end_time] column to a normalized version of the date/time value specified.
     *
     * @param  mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftEndTime($v)
    {
        $dt = PropelDateTime::newInstance($v, null, 'DateTime');
        if ($this->draft_end_time !== null || $dt !== null) {
            if ($this->draft_end_time === null || $dt === null || $dt->format("Y-m-d H:i:s") !== $this->draft_end_time->format("Y-m-d H:i:s")) {
                $this->draft_end_time = $dt === null ? null : clone $dt;
                $this->modifiedColumns[DraftTableMap::COL_DRAFT_END_TIME] = true;
            }
        } // if either are not null

        return $this;
    } // setDraftEndTime()

    /**
     * Set the value of [draft_current_round] column.
     *
     * @param int $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftCurrentRound($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->draft_current_round !== $v) {
            $this->draft_current_round = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_CURRENT_ROUND] = true;
        }

        return $this;
    } // setDraftCurrentRound()

    /**
     * Set the value of [draft_current_pick] column.
     *
     * @param int $v new value
     * @return $this|\Draft The current object (for fluent API support)
     */
    public function setDraftCurrentPick($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->draft_current_pick !== $v) {
            $this->draft_current_pick = $v;
            $this->modifiedColumns[DraftTableMap::COL_DRAFT_CURRENT_PICK] = true;
        }

        return $this;
    } // setDraftCurrentPick()

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
            if ($this->draft_counter !== 0) {
                return false;
            }

            if ($this->draft_rounds !== 0) {
                return false;
            }

            if ($this->draft_current_round !== 1) {
                return false;
            }

            if ($this->draft_current_pick !== 1) {
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

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : DraftTableMap::translateFieldName('DraftId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : DraftTableMap::translateFieldName('DraftCreateTime', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->draft_create_time = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : DraftTableMap::translateFieldName('DraftName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_name = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : DraftTableMap::translateFieldName('DraftSport', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_sport = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : DraftTableMap::translateFieldName('DraftStatus', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_status = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : DraftTableMap::translateFieldName('DraftCounter', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_counter = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 6 + $startcol : DraftTableMap::translateFieldName('DraftStyle', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_style = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 7 + $startcol : DraftTableMap::translateFieldName('DraftRounds', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_rounds = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 8 + $startcol : DraftTableMap::translateFieldName('DraftPassword', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_password = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 9 + $startcol : DraftTableMap::translateFieldName('DraftStartTime', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->draft_start_time = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 10 + $startcol : DraftTableMap::translateFieldName('DraftEndTime', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->draft_end_time = (null !== $col) ? PropelDateTime::newInstance($col, null, 'DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 11 + $startcol : DraftTableMap::translateFieldName('DraftCurrentRound', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_current_round = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 12 + $startcol : DraftTableMap::translateFieldName('DraftCurrentPick', TableMap::TYPE_PHPNAME, $indexType)];
            $this->draft_current_pick = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 13; // 13 = DraftTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\Draft'), 0, $e);
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
            $con = Propel::getServiceContainer()->getReadConnection(DraftTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildDraftQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
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
     * @see Draft::setDeleted()
     * @see Draft::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(DraftTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildDraftQuery::create()
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
            $con = Propel::getServiceContainer()->getWriteConnection(DraftTableMap::DATABASE_NAME);
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
                DraftTableMap::addInstanceToPool($this);
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

        $this->modifiedColumns[DraftTableMap::COL_DRAFT_ID] = true;
        if (null !== $this->draft_id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . DraftTableMap::COL_DRAFT_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_ID)) {
            $modifiedColumns[':p' . $index++]  = 'draft_id';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_CREATE_TIME)) {
            $modifiedColumns[':p' . $index++]  = 'draft_create_time';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'draft_name';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_SPORT)) {
            $modifiedColumns[':p' . $index++]  = 'draft_sport';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_STATUS)) {
            $modifiedColumns[':p' . $index++]  = 'draft_status';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_COUNTER)) {
            $modifiedColumns[':p' . $index++]  = 'draft_counter';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_STYLE)) {
            $modifiedColumns[':p' . $index++]  = 'draft_style';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_ROUNDS)) {
            $modifiedColumns[':p' . $index++]  = 'draft_rounds';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_PASSWORD)) {
            $modifiedColumns[':p' . $index++]  = 'draft_password';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_START_TIME)) {
            $modifiedColumns[':p' . $index++]  = 'draft_start_time';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_END_TIME)) {
            $modifiedColumns[':p' . $index++]  = 'draft_end_time';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_CURRENT_ROUND)) {
            $modifiedColumns[':p' . $index++]  = 'draft_current_round';
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_CURRENT_PICK)) {
            $modifiedColumns[':p' . $index++]  = 'draft_current_pick';
        }

        $sql = sprintf(
            'INSERT INTO draft (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'draft_id':
                        $stmt->bindValue($identifier, $this->draft_id, PDO::PARAM_INT);
                        break;
                    case 'draft_create_time':
                        $stmt->bindValue($identifier, $this->draft_create_time ? $this->draft_create_time->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'draft_name':
                        $stmt->bindValue($identifier, $this->draft_name, PDO::PARAM_STR);
                        break;
                    case 'draft_sport':
                        $stmt->bindValue($identifier, $this->draft_sport, PDO::PARAM_STR);
                        break;
                    case 'draft_status':
                        $stmt->bindValue($identifier, $this->draft_status, PDO::PARAM_STR);
                        break;
                    case 'draft_counter':
                        $stmt->bindValue($identifier, $this->draft_counter, PDO::PARAM_INT);
                        break;
                    case 'draft_style':
                        $stmt->bindValue($identifier, $this->draft_style, PDO::PARAM_STR);
                        break;
                    case 'draft_rounds':
                        $stmt->bindValue($identifier, $this->draft_rounds, PDO::PARAM_INT);
                        break;
                    case 'draft_password':
                        $stmt->bindValue($identifier, $this->draft_password, PDO::PARAM_STR);
                        break;
                    case 'draft_start_time':
                        $stmt->bindValue($identifier, $this->draft_start_time ? $this->draft_start_time->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'draft_end_time':
                        $stmt->bindValue($identifier, $this->draft_end_time ? $this->draft_end_time->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'draft_current_round':
                        $stmt->bindValue($identifier, $this->draft_current_round, PDO::PARAM_INT);
                        break;
                    case 'draft_current_pick':
                        $stmt->bindValue($identifier, $this->draft_current_pick, PDO::PARAM_INT);
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
        $this->setDraftId($pk);

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
        $pos = DraftTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
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
                return $this->getDraftId();
                break;
            case 1:
                return $this->getDraftCreateTime();
                break;
            case 2:
                return $this->getDraftName();
                break;
            case 3:
                return $this->getDraftSport();
                break;
            case 4:
                return $this->getDraftStatus();
                break;
            case 5:
                return $this->getDraftCounter();
                break;
            case 6:
                return $this->getDraftStyle();
                break;
            case 7:
                return $this->getDraftRounds();
                break;
            case 8:
                return $this->getDraftPassword();
                break;
            case 9:
                return $this->getDraftStartTime();
                break;
            case 10:
                return $this->getDraftEndTime();
                break;
            case 11:
                return $this->getDraftCurrentRound();
                break;
            case 12:
                return $this->getDraftCurrentPick();
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

        if (isset($alreadyDumpedObjects['Draft'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Draft'][$this->hashCode()] = true;
        $keys = DraftTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getDraftId(),
            $keys[1] => $this->getDraftCreateTime(),
            $keys[2] => $this->getDraftName(),
            $keys[3] => $this->getDraftSport(),
            $keys[4] => $this->getDraftStatus(),
            $keys[5] => $this->getDraftCounter(),
            $keys[6] => $this->getDraftStyle(),
            $keys[7] => $this->getDraftRounds(),
            $keys[8] => $this->getDraftPassword(),
            $keys[9] => $this->getDraftStartTime(),
            $keys[10] => $this->getDraftEndTime(),
            $keys[11] => $this->getDraftCurrentRound(),
            $keys[12] => $this->getDraftCurrentPick(),
        );

        $utc = new \DateTimeZone('utc');
        if ($result[$keys[1]] instanceof \DateTime) {
            // When changing timezone we don't want to change existing instances
            $dateTime = clone $result[$keys[1]];
            $result[$keys[1]] = $dateTime->setTimezone($utc)->format('Y-m-d\TH:i:s\Z');
        }

        if ($result[$keys[9]] instanceof \DateTime) {
            // When changing timezone we don't want to change existing instances
            $dateTime = clone $result[$keys[9]];
            $result[$keys[9]] = $dateTime->setTimezone($utc)->format('Y-m-d\TH:i:s\Z');
        }

        if ($result[$keys[10]] instanceof \DateTime) {
            // When changing timezone we don't want to change existing instances
            $dateTime = clone $result[$keys[10]];
            $result[$keys[10]] = $dateTime->setTimezone($utc)->format('Y-m-d\TH:i:s\Z');
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
     * @return $this|\Draft
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = DraftTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\Draft
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setDraftId($value);
                break;
            case 1:
                $this->setDraftCreateTime($value);
                break;
            case 2:
                $this->setDraftName($value);
                break;
            case 3:
                $this->setDraftSport($value);
                break;
            case 4:
                $this->setDraftStatus($value);
                break;
            case 5:
                $this->setDraftCounter($value);
                break;
            case 6:
                $this->setDraftStyle($value);
                break;
            case 7:
                $this->setDraftRounds($value);
                break;
            case 8:
                $this->setDraftPassword($value);
                break;
            case 9:
                $this->setDraftStartTime($value);
                break;
            case 10:
                $this->setDraftEndTime($value);
                break;
            case 11:
                $this->setDraftCurrentRound($value);
                break;
            case 12:
                $this->setDraftCurrentPick($value);
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
        $keys = DraftTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setDraftId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setDraftCreateTime($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setDraftName($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setDraftSport($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setDraftStatus($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setDraftCounter($arr[$keys[5]]);
        }
        if (array_key_exists($keys[6], $arr)) {
            $this->setDraftStyle($arr[$keys[6]]);
        }
        if (array_key_exists($keys[7], $arr)) {
            $this->setDraftRounds($arr[$keys[7]]);
        }
        if (array_key_exists($keys[8], $arr)) {
            $this->setDraftPassword($arr[$keys[8]]);
        }
        if (array_key_exists($keys[9], $arr)) {
            $this->setDraftStartTime($arr[$keys[9]]);
        }
        if (array_key_exists($keys[10], $arr)) {
            $this->setDraftEndTime($arr[$keys[10]]);
        }
        if (array_key_exists($keys[11], $arr)) {
            $this->setDraftCurrentRound($arr[$keys[11]]);
        }
        if (array_key_exists($keys[12], $arr)) {
            $this->setDraftCurrentPick($arr[$keys[12]]);
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
     * @return $this|\Draft The current object, for fluid interface
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
        $criteria = new Criteria(DraftTableMap::DATABASE_NAME);

        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_ID)) {
            $criteria->add(DraftTableMap::COL_DRAFT_ID, $this->draft_id);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_CREATE_TIME)) {
            $criteria->add(DraftTableMap::COL_DRAFT_CREATE_TIME, $this->draft_create_time);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_NAME)) {
            $criteria->add(DraftTableMap::COL_DRAFT_NAME, $this->draft_name);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_SPORT)) {
            $criteria->add(DraftTableMap::COL_DRAFT_SPORT, $this->draft_sport);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_STATUS)) {
            $criteria->add(DraftTableMap::COL_DRAFT_STATUS, $this->draft_status);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_COUNTER)) {
            $criteria->add(DraftTableMap::COL_DRAFT_COUNTER, $this->draft_counter);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_STYLE)) {
            $criteria->add(DraftTableMap::COL_DRAFT_STYLE, $this->draft_style);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_ROUNDS)) {
            $criteria->add(DraftTableMap::COL_DRAFT_ROUNDS, $this->draft_rounds);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_PASSWORD)) {
            $criteria->add(DraftTableMap::COL_DRAFT_PASSWORD, $this->draft_password);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_START_TIME)) {
            $criteria->add(DraftTableMap::COL_DRAFT_START_TIME, $this->draft_start_time);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_END_TIME)) {
            $criteria->add(DraftTableMap::COL_DRAFT_END_TIME, $this->draft_end_time);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_CURRENT_ROUND)) {
            $criteria->add(DraftTableMap::COL_DRAFT_CURRENT_ROUND, $this->draft_current_round);
        }
        if ($this->isColumnModified(DraftTableMap::COL_DRAFT_CURRENT_PICK)) {
            $criteria->add(DraftTableMap::COL_DRAFT_CURRENT_PICK, $this->draft_current_pick);
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
        $criteria = ChildDraftQuery::create();
        $criteria->add(DraftTableMap::COL_DRAFT_ID, $this->draft_id);

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
        $validPk = null !== $this->getDraftId();

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
        return $this->getDraftId();
    }

    /**
     * Generic method to set the primary key (draft_id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setDraftId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getDraftId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \Draft (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setDraftCreateTime($this->getDraftCreateTime());
        $copyObj->setDraftName($this->getDraftName());
        $copyObj->setDraftSport($this->getDraftSport());
        $copyObj->setDraftStatus($this->getDraftStatus());
        $copyObj->setDraftCounter($this->getDraftCounter());
        $copyObj->setDraftStyle($this->getDraftStyle());
        $copyObj->setDraftRounds($this->getDraftRounds());
        $copyObj->setDraftPassword($this->getDraftPassword());
        $copyObj->setDraftStartTime($this->getDraftStartTime());
        $copyObj->setDraftEndTime($this->getDraftEndTime());
        $copyObj->setDraftCurrentRound($this->getDraftCurrentRound());
        $copyObj->setDraftCurrentPick($this->getDraftCurrentPick());
        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setDraftId(NULL); // this is a auto-increment column, so set to default value
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
     * @return \Draft Clone of current object.
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
        $this->draft_id = null;
        $this->draft_create_time = null;
        $this->draft_name = null;
        $this->draft_sport = null;
        $this->draft_status = null;
        $this->draft_counter = null;
        $this->draft_style = null;
        $this->draft_rounds = null;
        $this->draft_password = null;
        $this->draft_start_time = null;
        $this->draft_end_time = null;
        $this->draft_current_round = null;
        $this->draft_current_pick = null;
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
        return (string) $this->exportTo(DraftTableMap::DEFAULT_STRING_FORMAT);
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
