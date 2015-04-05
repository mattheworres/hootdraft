<?php

namespace Base;

use \Draft as ChildDraft;
use \DraftQuery as ChildDraftQuery;
use \Exception;
use \PDO;
use Map\DraftTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'draft' table.
 *
 *
 *
 * @method     ChildDraftQuery orderByDraftId($order = Criteria::ASC) Order by the draft_id column
 * @method     ChildDraftQuery orderByDraftCreateTime($order = Criteria::ASC) Order by the draft_create_time column
 * @method     ChildDraftQuery orderByDraftName($order = Criteria::ASC) Order by the draft_name column
 * @method     ChildDraftQuery orderByDraftSport($order = Criteria::ASC) Order by the draft_sport column
 * @method     ChildDraftQuery orderByDraftStatus($order = Criteria::ASC) Order by the draft_status column
 * @method     ChildDraftQuery orderByDraftCounter($order = Criteria::ASC) Order by the draft_counter column
 * @method     ChildDraftQuery orderByDraftStyle($order = Criteria::ASC) Order by the draft_style column
 * @method     ChildDraftQuery orderByDraftRounds($order = Criteria::ASC) Order by the draft_rounds column
 * @method     ChildDraftQuery orderByDraftPassword($order = Criteria::ASC) Order by the draft_password column
 * @method     ChildDraftQuery orderByDraftStartTime($order = Criteria::ASC) Order by the draft_start_time column
 * @method     ChildDraftQuery orderByDraftEndTime($order = Criteria::ASC) Order by the draft_end_time column
 * @method     ChildDraftQuery orderByDraftCurrentRound($order = Criteria::ASC) Order by the draft_current_round column
 * @method     ChildDraftQuery orderByDraftCurrentPick($order = Criteria::ASC) Order by the draft_current_pick column
 *
 * @method     ChildDraftQuery groupByDraftId() Group by the draft_id column
 * @method     ChildDraftQuery groupByDraftCreateTime() Group by the draft_create_time column
 * @method     ChildDraftQuery groupByDraftName() Group by the draft_name column
 * @method     ChildDraftQuery groupByDraftSport() Group by the draft_sport column
 * @method     ChildDraftQuery groupByDraftStatus() Group by the draft_status column
 * @method     ChildDraftQuery groupByDraftCounter() Group by the draft_counter column
 * @method     ChildDraftQuery groupByDraftStyle() Group by the draft_style column
 * @method     ChildDraftQuery groupByDraftRounds() Group by the draft_rounds column
 * @method     ChildDraftQuery groupByDraftPassword() Group by the draft_password column
 * @method     ChildDraftQuery groupByDraftStartTime() Group by the draft_start_time column
 * @method     ChildDraftQuery groupByDraftEndTime() Group by the draft_end_time column
 * @method     ChildDraftQuery groupByDraftCurrentRound() Group by the draft_current_round column
 * @method     ChildDraftQuery groupByDraftCurrentPick() Group by the draft_current_pick column
 *
 * @method     ChildDraftQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildDraftQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildDraftQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildDraft findOne(ConnectionInterface $con = null) Return the first ChildDraft matching the query
 * @method     ChildDraft findOneOrCreate(ConnectionInterface $con = null) Return the first ChildDraft matching the query, or a new ChildDraft object populated from the query conditions when no match is found
 *
 * @method     ChildDraft findOneByDraftId(int $draft_id) Return the first ChildDraft filtered by the draft_id column
 * @method     ChildDraft findOneByDraftCreateTime(string $draft_create_time) Return the first ChildDraft filtered by the draft_create_time column
 * @method     ChildDraft findOneByDraftName(string $draft_name) Return the first ChildDraft filtered by the draft_name column
 * @method     ChildDraft findOneByDraftSport(string $draft_sport) Return the first ChildDraft filtered by the draft_sport column
 * @method     ChildDraft findOneByDraftStatus(string $draft_status) Return the first ChildDraft filtered by the draft_status column
 * @method     ChildDraft findOneByDraftCounter(int $draft_counter) Return the first ChildDraft filtered by the draft_counter column
 * @method     ChildDraft findOneByDraftStyle(string $draft_style) Return the first ChildDraft filtered by the draft_style column
 * @method     ChildDraft findOneByDraftRounds(int $draft_rounds) Return the first ChildDraft filtered by the draft_rounds column
 * @method     ChildDraft findOneByDraftPassword(string $draft_password) Return the first ChildDraft filtered by the draft_password column
 * @method     ChildDraft findOneByDraftStartTime(string $draft_start_time) Return the first ChildDraft filtered by the draft_start_time column
 * @method     ChildDraft findOneByDraftEndTime(string $draft_end_time) Return the first ChildDraft filtered by the draft_end_time column
 * @method     ChildDraft findOneByDraftCurrentRound(int $draft_current_round) Return the first ChildDraft filtered by the draft_current_round column
 * @method     ChildDraft findOneByDraftCurrentPick(int $draft_current_pick) Return the first ChildDraft filtered by the draft_current_pick column *

 * @method     ChildDraft requirePk($key, ConnectionInterface $con = null) Return the ChildDraft by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOne(ConnectionInterface $con = null) Return the first ChildDraft matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildDraft requireOneByDraftId(int $draft_id) Return the first ChildDraft filtered by the draft_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftCreateTime(string $draft_create_time) Return the first ChildDraft filtered by the draft_create_time column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftName(string $draft_name) Return the first ChildDraft filtered by the draft_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftSport(string $draft_sport) Return the first ChildDraft filtered by the draft_sport column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftStatus(string $draft_status) Return the first ChildDraft filtered by the draft_status column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftCounter(int $draft_counter) Return the first ChildDraft filtered by the draft_counter column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftStyle(string $draft_style) Return the first ChildDraft filtered by the draft_style column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftRounds(int $draft_rounds) Return the first ChildDraft filtered by the draft_rounds column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftPassword(string $draft_password) Return the first ChildDraft filtered by the draft_password column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftStartTime(string $draft_start_time) Return the first ChildDraft filtered by the draft_start_time column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftEndTime(string $draft_end_time) Return the first ChildDraft filtered by the draft_end_time column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftCurrentRound(int $draft_current_round) Return the first ChildDraft filtered by the draft_current_round column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildDraft requireOneByDraftCurrentPick(int $draft_current_pick) Return the first ChildDraft filtered by the draft_current_pick column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildDraft[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildDraft objects based on current ModelCriteria
 * @method     ChildDraft[]|ObjectCollection findByDraftId(int $draft_id) Return ChildDraft objects filtered by the draft_id column
 * @method     ChildDraft[]|ObjectCollection findByDraftCreateTime(string $draft_create_time) Return ChildDraft objects filtered by the draft_create_time column
 * @method     ChildDraft[]|ObjectCollection findByDraftName(string $draft_name) Return ChildDraft objects filtered by the draft_name column
 * @method     ChildDraft[]|ObjectCollection findByDraftSport(string $draft_sport) Return ChildDraft objects filtered by the draft_sport column
 * @method     ChildDraft[]|ObjectCollection findByDraftStatus(string $draft_status) Return ChildDraft objects filtered by the draft_status column
 * @method     ChildDraft[]|ObjectCollection findByDraftCounter(int $draft_counter) Return ChildDraft objects filtered by the draft_counter column
 * @method     ChildDraft[]|ObjectCollection findByDraftStyle(string $draft_style) Return ChildDraft objects filtered by the draft_style column
 * @method     ChildDraft[]|ObjectCollection findByDraftRounds(int $draft_rounds) Return ChildDraft objects filtered by the draft_rounds column
 * @method     ChildDraft[]|ObjectCollection findByDraftPassword(string $draft_password) Return ChildDraft objects filtered by the draft_password column
 * @method     ChildDraft[]|ObjectCollection findByDraftStartTime(string $draft_start_time) Return ChildDraft objects filtered by the draft_start_time column
 * @method     ChildDraft[]|ObjectCollection findByDraftEndTime(string $draft_end_time) Return ChildDraft objects filtered by the draft_end_time column
 * @method     ChildDraft[]|ObjectCollection findByDraftCurrentRound(int $draft_current_round) Return ChildDraft objects filtered by the draft_current_round column
 * @method     ChildDraft[]|ObjectCollection findByDraftCurrentPick(int $draft_current_pick) Return ChildDraft objects filtered by the draft_current_pick column
 * @method     ChildDraft[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class DraftQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \Base\DraftQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'phpdraft', $modelName = '\\Draft', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildDraftQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildDraftQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildDraftQuery) {
            return $criteria;
        }
        $query = new ChildDraftQuery();
        if (null !== $modelAlias) {
            $query->setModelAlias($modelAlias);
        }
        if ($criteria instanceof Criteria) {
            $query->mergeWith($criteria);
        }

        return $query;
    }

    /**
     * Find object by primary key.
     * Propel uses the instance pool to skip the database if the object exists.
     * Go fast if the query is untouched.
     *
     * <code>
     * $obj  = $c->findPk(12, $con);
     * </code>
     *
     * @param mixed $key Primary key to use for the query
     * @param ConnectionInterface $con an optional connection object
     *
     * @return ChildDraft|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = DraftTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(DraftTableMap::DATABASE_NAME);
        }
        $this->basePreSelect($con);
        if ($this->formatter || $this->modelAlias || $this->with || $this->select
         || $this->selectColumns || $this->asColumns || $this->selectModifiers
         || $this->map || $this->having || $this->joins) {
            return $this->findPkComplex($key, $con);
        } else {
            return $this->findPkSimple($key, $con);
        }
    }

    /**
     * Find object by primary key using raw SQL to go fast.
     * Bypass doSelect() and the object formatter by using generated code.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return ChildDraft A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT draft_id, draft_create_time, draft_name, draft_sport, draft_status, draft_counter, draft_style, draft_rounds, draft_password, draft_start_time, draft_end_time, draft_current_round, draft_current_pick FROM draft WHERE draft_id = :p0';
        try {
            $stmt = $con->prepare($sql);
            $stmt->bindValue(':p0', $key, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute SELECT statement [%s]', $sql), 0, $e);
        }
        $obj = null;
        if ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            /** @var ChildDraft $obj */
            $obj = new ChildDraft();
            $obj->hydrate($row);
            DraftTableMap::addInstanceToPool($obj, (string) $key);
        }
        $stmt->closeCursor();

        return $obj;
    }

    /**
     * Find object by primary key.
     *
     * @param     mixed $key Primary key to use for the query
     * @param     ConnectionInterface $con A connection object
     *
     * @return ChildDraft|array|mixed the result, formatted by the current formatter
     */
    protected function findPkComplex($key, ConnectionInterface $con)
    {
        // As the query uses a PK condition, no limit(1) is necessary.
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKey($key)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->formatOne($dataFetcher);
    }

    /**
     * Find objects by primary key
     * <code>
     * $objs = $c->findPks(array(12, 56, 832), $con);
     * </code>
     * @param     array $keys Primary keys to use for the query
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ObjectCollection|array|mixed the list of results, formatted by the current formatter
     */
    public function findPks($keys, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getReadConnection($this->getDbName());
        }
        $this->basePreSelect($con);
        $criteria = $this->isKeepQuery() ? clone $this : $this;
        $dataFetcher = $criteria
            ->filterByPrimaryKeys($keys)
            ->doSelect($con);

        return $criteria->getFormatter()->init($criteria)->format($dataFetcher);
    }

    /**
     * Filter the query by primary key
     *
     * @param     mixed $key Primary key to use for the query
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the draft_id column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftId(1234); // WHERE draft_id = 1234
     * $query->filterByDraftId(array(12, 34)); // WHERE draft_id IN (12, 34)
     * $query->filterByDraftId(array('min' => 12)); // WHERE draft_id > 12
     * </code>
     *
     * @param     mixed $draftId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftId($draftId = null, $comparison = null)
    {
        if (is_array($draftId)) {
            $useMinMax = false;
            if (isset($draftId['min'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_ID, $draftId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftId['max'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_ID, $draftId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_ID, $draftId, $comparison);
    }

    /**
     * Filter the query on the draft_create_time column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftCreateTime('2011-03-14'); // WHERE draft_create_time = '2011-03-14'
     * $query->filterByDraftCreateTime('now'); // WHERE draft_create_time = '2011-03-14'
     * $query->filterByDraftCreateTime(array('max' => 'yesterday')); // WHERE draft_create_time > '2011-03-13'
     * </code>
     *
     * @param     mixed $draftCreateTime The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftCreateTime($draftCreateTime = null, $comparison = null)
    {
        if (is_array($draftCreateTime)) {
            $useMinMax = false;
            if (isset($draftCreateTime['min'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_CREATE_TIME, $draftCreateTime['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftCreateTime['max'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_CREATE_TIME, $draftCreateTime['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_CREATE_TIME, $draftCreateTime, $comparison);
    }

    /**
     * Filter the query on the draft_name column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftName('fooValue');   // WHERE draft_name = 'fooValue'
     * $query->filterByDraftName('%fooValue%'); // WHERE draft_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $draftName The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftName($draftName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($draftName)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $draftName)) {
                $draftName = str_replace('*', '%', $draftName);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_NAME, $draftName, $comparison);
    }

    /**
     * Filter the query on the draft_sport column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftSport('fooValue');   // WHERE draft_sport = 'fooValue'
     * $query->filterByDraftSport('%fooValue%'); // WHERE draft_sport LIKE '%fooValue%'
     * </code>
     *
     * @param     string $draftSport The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftSport($draftSport = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($draftSport)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $draftSport)) {
                $draftSport = str_replace('*', '%', $draftSport);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_SPORT, $draftSport, $comparison);
    }

    /**
     * Filter the query on the draft_status column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftStatus('fooValue');   // WHERE draft_status = 'fooValue'
     * $query->filterByDraftStatus('%fooValue%'); // WHERE draft_status LIKE '%fooValue%'
     * </code>
     *
     * @param     string $draftStatus The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftStatus($draftStatus = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($draftStatus)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $draftStatus)) {
                $draftStatus = str_replace('*', '%', $draftStatus);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_STATUS, $draftStatus, $comparison);
    }

    /**
     * Filter the query on the draft_counter column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftCounter(1234); // WHERE draft_counter = 1234
     * $query->filterByDraftCounter(array(12, 34)); // WHERE draft_counter IN (12, 34)
     * $query->filterByDraftCounter(array('min' => 12)); // WHERE draft_counter > 12
     * </code>
     *
     * @param     mixed $draftCounter The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftCounter($draftCounter = null, $comparison = null)
    {
        if (is_array($draftCounter)) {
            $useMinMax = false;
            if (isset($draftCounter['min'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_COUNTER, $draftCounter['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftCounter['max'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_COUNTER, $draftCounter['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_COUNTER, $draftCounter, $comparison);
    }

    /**
     * Filter the query on the draft_style column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftStyle('fooValue');   // WHERE draft_style = 'fooValue'
     * $query->filterByDraftStyle('%fooValue%'); // WHERE draft_style LIKE '%fooValue%'
     * </code>
     *
     * @param     string $draftStyle The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftStyle($draftStyle = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($draftStyle)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $draftStyle)) {
                $draftStyle = str_replace('*', '%', $draftStyle);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_STYLE, $draftStyle, $comparison);
    }

    /**
     * Filter the query on the draft_rounds column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftRounds(1234); // WHERE draft_rounds = 1234
     * $query->filterByDraftRounds(array(12, 34)); // WHERE draft_rounds IN (12, 34)
     * $query->filterByDraftRounds(array('min' => 12)); // WHERE draft_rounds > 12
     * </code>
     *
     * @param     mixed $draftRounds The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftRounds($draftRounds = null, $comparison = null)
    {
        if (is_array($draftRounds)) {
            $useMinMax = false;
            if (isset($draftRounds['min'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_ROUNDS, $draftRounds['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftRounds['max'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_ROUNDS, $draftRounds['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_ROUNDS, $draftRounds, $comparison);
    }

    /**
     * Filter the query on the draft_password column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftPassword('fooValue');   // WHERE draft_password = 'fooValue'
     * $query->filterByDraftPassword('%fooValue%'); // WHERE draft_password LIKE '%fooValue%'
     * </code>
     *
     * @param     string $draftPassword The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftPassword($draftPassword = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($draftPassword)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $draftPassword)) {
                $draftPassword = str_replace('*', '%', $draftPassword);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_PASSWORD, $draftPassword, $comparison);
    }

    /**
     * Filter the query on the draft_start_time column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftStartTime('2011-03-14'); // WHERE draft_start_time = '2011-03-14'
     * $query->filterByDraftStartTime('now'); // WHERE draft_start_time = '2011-03-14'
     * $query->filterByDraftStartTime(array('max' => 'yesterday')); // WHERE draft_start_time > '2011-03-13'
     * </code>
     *
     * @param     mixed $draftStartTime The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftStartTime($draftStartTime = null, $comparison = null)
    {
        if (is_array($draftStartTime)) {
            $useMinMax = false;
            if (isset($draftStartTime['min'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_START_TIME, $draftStartTime['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftStartTime['max'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_START_TIME, $draftStartTime['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_START_TIME, $draftStartTime, $comparison);
    }

    /**
     * Filter the query on the draft_end_time column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftEndTime('2011-03-14'); // WHERE draft_end_time = '2011-03-14'
     * $query->filterByDraftEndTime('now'); // WHERE draft_end_time = '2011-03-14'
     * $query->filterByDraftEndTime(array('max' => 'yesterday')); // WHERE draft_end_time > '2011-03-13'
     * </code>
     *
     * @param     mixed $draftEndTime The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftEndTime($draftEndTime = null, $comparison = null)
    {
        if (is_array($draftEndTime)) {
            $useMinMax = false;
            if (isset($draftEndTime['min'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_END_TIME, $draftEndTime['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftEndTime['max'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_END_TIME, $draftEndTime['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_END_TIME, $draftEndTime, $comparison);
    }

    /**
     * Filter the query on the draft_current_round column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftCurrentRound(1234); // WHERE draft_current_round = 1234
     * $query->filterByDraftCurrentRound(array(12, 34)); // WHERE draft_current_round IN (12, 34)
     * $query->filterByDraftCurrentRound(array('min' => 12)); // WHERE draft_current_round > 12
     * </code>
     *
     * @param     mixed $draftCurrentRound The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftCurrentRound($draftCurrentRound = null, $comparison = null)
    {
        if (is_array($draftCurrentRound)) {
            $useMinMax = false;
            if (isset($draftCurrentRound['min'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_CURRENT_ROUND, $draftCurrentRound['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftCurrentRound['max'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_CURRENT_ROUND, $draftCurrentRound['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_CURRENT_ROUND, $draftCurrentRound, $comparison);
    }

    /**
     * Filter the query on the draft_current_pick column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftCurrentPick(1234); // WHERE draft_current_pick = 1234
     * $query->filterByDraftCurrentPick(array(12, 34)); // WHERE draft_current_pick IN (12, 34)
     * $query->filterByDraftCurrentPick(array('min' => 12)); // WHERE draft_current_pick > 12
     * </code>
     *
     * @param     mixed $draftCurrentPick The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function filterByDraftCurrentPick($draftCurrentPick = null, $comparison = null)
    {
        if (is_array($draftCurrentPick)) {
            $useMinMax = false;
            if (isset($draftCurrentPick['min'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_CURRENT_PICK, $draftCurrentPick['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftCurrentPick['max'])) {
                $this->addUsingAlias(DraftTableMap::COL_DRAFT_CURRENT_PICK, $draftCurrentPick['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(DraftTableMap::COL_DRAFT_CURRENT_PICK, $draftCurrentPick, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildDraft $draft Object to remove from the list of results
     *
     * @return $this|ChildDraftQuery The current query, for fluid interface
     */
    public function prune($draft = null)
    {
        if ($draft) {
            $this->addUsingAlias(DraftTableMap::COL_DRAFT_ID, $draft->getDraftId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the draft table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(DraftTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            DraftTableMap::clearInstancePool();
            DraftTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

    /**
     * Performs a DELETE on the database based on the current ModelCriteria
     *
     * @param ConnectionInterface $con the connection to use
     * @return int             The number of affected rows (if supported by underlying database driver).  This includes CASCADE-related rows
     *                         if supported by native driver or if emulated using Propel.
     * @throws PropelException Any exceptions caught during processing will be
     *                         rethrown wrapped into a PropelException.
     */
    public function delete(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(DraftTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(DraftTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            DraftTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            DraftTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // DraftQuery
