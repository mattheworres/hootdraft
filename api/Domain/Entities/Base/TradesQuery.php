<?php

namespace Base;

use \Trades as ChildTrades;
use \TradesQuery as ChildTradesQuery;
use \Exception;
use \PDO;
use Map\TradesTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'trades' table.
 *
 *
 *
 * @method     ChildTradesQuery orderByTradeId($order = Criteria::ASC) Order by the trade_id column
 * @method     ChildTradesQuery orderByDraftId($order = Criteria::ASC) Order by the draft_id column
 * @method     ChildTradesQuery orderByManager1Id($order = Criteria::ASC) Order by the manager1_id column
 * @method     ChildTradesQuery orderByManager2Id($order = Criteria::ASC) Order by the manager2_id column
 * @method     ChildTradesQuery orderByTradeTime($order = Criteria::ASC) Order by the trade_time column
 *
 * @method     ChildTradesQuery groupByTradeId() Group by the trade_id column
 * @method     ChildTradesQuery groupByDraftId() Group by the draft_id column
 * @method     ChildTradesQuery groupByManager1Id() Group by the manager1_id column
 * @method     ChildTradesQuery groupByManager2Id() Group by the manager2_id column
 * @method     ChildTradesQuery groupByTradeTime() Group by the trade_time column
 *
 * @method     ChildTradesQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildTradesQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildTradesQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildTrades findOne(ConnectionInterface $con = null) Return the first ChildTrades matching the query
 * @method     ChildTrades findOneOrCreate(ConnectionInterface $con = null) Return the first ChildTrades matching the query, or a new ChildTrades object populated from the query conditions when no match is found
 *
 * @method     ChildTrades findOneByTradeId(int $trade_id) Return the first ChildTrades filtered by the trade_id column
 * @method     ChildTrades findOneByDraftId(int $draft_id) Return the first ChildTrades filtered by the draft_id column
 * @method     ChildTrades findOneByManager1Id(int $manager1_id) Return the first ChildTrades filtered by the manager1_id column
 * @method     ChildTrades findOneByManager2Id(int $manager2_id) Return the first ChildTrades filtered by the manager2_id column
 * @method     ChildTrades findOneByTradeTime(string $trade_time) Return the first ChildTrades filtered by the trade_time column *

 * @method     ChildTrades requirePk($key, ConnectionInterface $con = null) Return the ChildTrades by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTrades requireOne(ConnectionInterface $con = null) Return the first ChildTrades matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildTrades requireOneByTradeId(int $trade_id) Return the first ChildTrades filtered by the trade_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTrades requireOneByDraftId(int $draft_id) Return the first ChildTrades filtered by the draft_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTrades requireOneByManager1Id(int $manager1_id) Return the first ChildTrades filtered by the manager1_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTrades requireOneByManager2Id(int $manager2_id) Return the first ChildTrades filtered by the manager2_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTrades requireOneByTradeTime(string $trade_time) Return the first ChildTrades filtered by the trade_time column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildTrades[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildTrades objects based on current ModelCriteria
 * @method     ChildTrades[]|ObjectCollection findByTradeId(int $trade_id) Return ChildTrades objects filtered by the trade_id column
 * @method     ChildTrades[]|ObjectCollection findByDraftId(int $draft_id) Return ChildTrades objects filtered by the draft_id column
 * @method     ChildTrades[]|ObjectCollection findByManager1Id(int $manager1_id) Return ChildTrades objects filtered by the manager1_id column
 * @method     ChildTrades[]|ObjectCollection findByManager2Id(int $manager2_id) Return ChildTrades objects filtered by the manager2_id column
 * @method     ChildTrades[]|ObjectCollection findByTradeTime(string $trade_time) Return ChildTrades objects filtered by the trade_time column
 * @method     ChildTrades[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class TradesQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \Base\TradesQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'phpdraft', $modelName = '\\Trades', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildTradesQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildTradesQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildTradesQuery) {
            return $criteria;
        }
        $query = new ChildTradesQuery();
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
     * @return ChildTrades|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = TradesTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(TradesTableMap::DATABASE_NAME);
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
     * @return ChildTrades A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT trade_id, draft_id, manager1_id, manager2_id, trade_time FROM trades WHERE trade_id = :p0';
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
            /** @var ChildTrades $obj */
            $obj = new ChildTrades();
            $obj->hydrate($row);
            TradesTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildTrades|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildTradesQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(TradesTableMap::COL_TRADE_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildTradesQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(TradesTableMap::COL_TRADE_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the trade_id column
     *
     * Example usage:
     * <code>
     * $query->filterByTradeId(1234); // WHERE trade_id = 1234
     * $query->filterByTradeId(array(12, 34)); // WHERE trade_id IN (12, 34)
     * $query->filterByTradeId(array('min' => 12)); // WHERE trade_id > 12
     * </code>
     *
     * @param     mixed $tradeId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradesQuery The current query, for fluid interface
     */
    public function filterByTradeId($tradeId = null, $comparison = null)
    {
        if (is_array($tradeId)) {
            $useMinMax = false;
            if (isset($tradeId['min'])) {
                $this->addUsingAlias(TradesTableMap::COL_TRADE_ID, $tradeId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($tradeId['max'])) {
                $this->addUsingAlias(TradesTableMap::COL_TRADE_ID, $tradeId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradesTableMap::COL_TRADE_ID, $tradeId, $comparison);
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
     * @return $this|ChildTradesQuery The current query, for fluid interface
     */
    public function filterByDraftId($draftId = null, $comparison = null)
    {
        if (is_array($draftId)) {
            $useMinMax = false;
            if (isset($draftId['min'])) {
                $this->addUsingAlias(TradesTableMap::COL_DRAFT_ID, $draftId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftId['max'])) {
                $this->addUsingAlias(TradesTableMap::COL_DRAFT_ID, $draftId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradesTableMap::COL_DRAFT_ID, $draftId, $comparison);
    }

    /**
     * Filter the query on the manager1_id column
     *
     * Example usage:
     * <code>
     * $query->filterByManager1Id(1234); // WHERE manager1_id = 1234
     * $query->filterByManager1Id(array(12, 34)); // WHERE manager1_id IN (12, 34)
     * $query->filterByManager1Id(array('min' => 12)); // WHERE manager1_id > 12
     * </code>
     *
     * @param     mixed $manager1Id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradesQuery The current query, for fluid interface
     */
    public function filterByManager1Id($manager1Id = null, $comparison = null)
    {
        if (is_array($manager1Id)) {
            $useMinMax = false;
            if (isset($manager1Id['min'])) {
                $this->addUsingAlias(TradesTableMap::COL_MANAGER1_ID, $manager1Id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($manager1Id['max'])) {
                $this->addUsingAlias(TradesTableMap::COL_MANAGER1_ID, $manager1Id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradesTableMap::COL_MANAGER1_ID, $manager1Id, $comparison);
    }

    /**
     * Filter the query on the manager2_id column
     *
     * Example usage:
     * <code>
     * $query->filterByManager2Id(1234); // WHERE manager2_id = 1234
     * $query->filterByManager2Id(array(12, 34)); // WHERE manager2_id IN (12, 34)
     * $query->filterByManager2Id(array('min' => 12)); // WHERE manager2_id > 12
     * </code>
     *
     * @param     mixed $manager2Id The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradesQuery The current query, for fluid interface
     */
    public function filterByManager2Id($manager2Id = null, $comparison = null)
    {
        if (is_array($manager2Id)) {
            $useMinMax = false;
            if (isset($manager2Id['min'])) {
                $this->addUsingAlias(TradesTableMap::COL_MANAGER2_ID, $manager2Id['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($manager2Id['max'])) {
                $this->addUsingAlias(TradesTableMap::COL_MANAGER2_ID, $manager2Id['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradesTableMap::COL_MANAGER2_ID, $manager2Id, $comparison);
    }

    /**
     * Filter the query on the trade_time column
     *
     * Example usage:
     * <code>
     * $query->filterByTradeTime('2011-03-14'); // WHERE trade_time = '2011-03-14'
     * $query->filterByTradeTime('now'); // WHERE trade_time = '2011-03-14'
     * $query->filterByTradeTime(array('max' => 'yesterday')); // WHERE trade_time > '2011-03-13'
     * </code>
     *
     * @param     mixed $tradeTime The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradesQuery The current query, for fluid interface
     */
    public function filterByTradeTime($tradeTime = null, $comparison = null)
    {
        if (is_array($tradeTime)) {
            $useMinMax = false;
            if (isset($tradeTime['min'])) {
                $this->addUsingAlias(TradesTableMap::COL_TRADE_TIME, $tradeTime['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($tradeTime['max'])) {
                $this->addUsingAlias(TradesTableMap::COL_TRADE_TIME, $tradeTime['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradesTableMap::COL_TRADE_TIME, $tradeTime, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildTrades $trades Object to remove from the list of results
     *
     * @return $this|ChildTradesQuery The current query, for fluid interface
     */
    public function prune($trades = null)
    {
        if ($trades) {
            $this->addUsingAlias(TradesTableMap::COL_TRADE_ID, $trades->getTradeId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the trades table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(TradesTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            TradesTableMap::clearInstancePool();
            TradesTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(TradesTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(TradesTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            TradesTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            TradesTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // TradesQuery
