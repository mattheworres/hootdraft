<?php

namespace Base;

use \TradeAssets as ChildTradeAssets;
use \TradeAssetsQuery as ChildTradeAssetsQuery;
use \Exception;
use \PDO;
use Map\TradeAssetsTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'trade_assets' table.
 *
 *
 *
 * @method     ChildTradeAssetsQuery orderByTradeassetId($order = Criteria::ASC) Order by the tradeasset_id column
 * @method     ChildTradeAssetsQuery orderByTradeId($order = Criteria::ASC) Order by the trade_id column
 * @method     ChildTradeAssetsQuery orderByPlayerId($order = Criteria::ASC) Order by the player_id column
 * @method     ChildTradeAssetsQuery orderByOldmanagerId($order = Criteria::ASC) Order by the oldmanager_id column
 * @method     ChildTradeAssetsQuery orderByNewmanagerId($order = Criteria::ASC) Order by the newmanager_id column
 * @method     ChildTradeAssetsQuery orderByWasDrafted($order = Criteria::ASC) Order by the was_drafted column
 *
 * @method     ChildTradeAssetsQuery groupByTradeassetId() Group by the tradeasset_id column
 * @method     ChildTradeAssetsQuery groupByTradeId() Group by the trade_id column
 * @method     ChildTradeAssetsQuery groupByPlayerId() Group by the player_id column
 * @method     ChildTradeAssetsQuery groupByOldmanagerId() Group by the oldmanager_id column
 * @method     ChildTradeAssetsQuery groupByNewmanagerId() Group by the newmanager_id column
 * @method     ChildTradeAssetsQuery groupByWasDrafted() Group by the was_drafted column
 *
 * @method     ChildTradeAssetsQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildTradeAssetsQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildTradeAssetsQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildTradeAssets findOne(ConnectionInterface $con = null) Return the first ChildTradeAssets matching the query
 * @method     ChildTradeAssets findOneOrCreate(ConnectionInterface $con = null) Return the first ChildTradeAssets matching the query, or a new ChildTradeAssets object populated from the query conditions when no match is found
 *
 * @method     ChildTradeAssets findOneByTradeassetId(int $tradeasset_id) Return the first ChildTradeAssets filtered by the tradeasset_id column
 * @method     ChildTradeAssets findOneByTradeId(int $trade_id) Return the first ChildTradeAssets filtered by the trade_id column
 * @method     ChildTradeAssets findOneByPlayerId(int $player_id) Return the first ChildTradeAssets filtered by the player_id column
 * @method     ChildTradeAssets findOneByOldmanagerId(int $oldmanager_id) Return the first ChildTradeAssets filtered by the oldmanager_id column
 * @method     ChildTradeAssets findOneByNewmanagerId(int $newmanager_id) Return the first ChildTradeAssets filtered by the newmanager_id column
 * @method     ChildTradeAssets findOneByWasDrafted(boolean $was_drafted) Return the first ChildTradeAssets filtered by the was_drafted column *

 * @method     ChildTradeAssets requirePk($key, ConnectionInterface $con = null) Return the ChildTradeAssets by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTradeAssets requireOne(ConnectionInterface $con = null) Return the first ChildTradeAssets matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildTradeAssets requireOneByTradeassetId(int $tradeasset_id) Return the first ChildTradeAssets filtered by the tradeasset_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTradeAssets requireOneByTradeId(int $trade_id) Return the first ChildTradeAssets filtered by the trade_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTradeAssets requireOneByPlayerId(int $player_id) Return the first ChildTradeAssets filtered by the player_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTradeAssets requireOneByOldmanagerId(int $oldmanager_id) Return the first ChildTradeAssets filtered by the oldmanager_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTradeAssets requireOneByNewmanagerId(int $newmanager_id) Return the first ChildTradeAssets filtered by the newmanager_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildTradeAssets requireOneByWasDrafted(boolean $was_drafted) Return the first ChildTradeAssets filtered by the was_drafted column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildTradeAssets[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildTradeAssets objects based on current ModelCriteria
 * @method     ChildTradeAssets[]|ObjectCollection findByTradeassetId(int $tradeasset_id) Return ChildTradeAssets objects filtered by the tradeasset_id column
 * @method     ChildTradeAssets[]|ObjectCollection findByTradeId(int $trade_id) Return ChildTradeAssets objects filtered by the trade_id column
 * @method     ChildTradeAssets[]|ObjectCollection findByPlayerId(int $player_id) Return ChildTradeAssets objects filtered by the player_id column
 * @method     ChildTradeAssets[]|ObjectCollection findByOldmanagerId(int $oldmanager_id) Return ChildTradeAssets objects filtered by the oldmanager_id column
 * @method     ChildTradeAssets[]|ObjectCollection findByNewmanagerId(int $newmanager_id) Return ChildTradeAssets objects filtered by the newmanager_id column
 * @method     ChildTradeAssets[]|ObjectCollection findByWasDrafted(boolean $was_drafted) Return ChildTradeAssets objects filtered by the was_drafted column
 * @method     ChildTradeAssets[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class TradeAssetsQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \Base\TradeAssetsQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'phpdraft', $modelName = '\\TradeAssets', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildTradeAssetsQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildTradeAssetsQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildTradeAssetsQuery) {
            return $criteria;
        }
        $query = new ChildTradeAssetsQuery();
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
     * @return ChildTradeAssets|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = TradeAssetsTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(TradeAssetsTableMap::DATABASE_NAME);
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
     * @return ChildTradeAssets A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT tradeasset_id, trade_id, player_id, oldmanager_id, newmanager_id, was_drafted FROM trade_assets WHERE tradeasset_id = :p0';
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
            /** @var ChildTradeAssets $obj */
            $obj = new ChildTradeAssets();
            $obj->hydrate($row);
            TradeAssetsTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildTradeAssets|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(TradeAssetsTableMap::COL_TRADEASSET_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(TradeAssetsTableMap::COL_TRADEASSET_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the tradeasset_id column
     *
     * Example usage:
     * <code>
     * $query->filterByTradeassetId(1234); // WHERE tradeasset_id = 1234
     * $query->filterByTradeassetId(array(12, 34)); // WHERE tradeasset_id IN (12, 34)
     * $query->filterByTradeassetId(array('min' => 12)); // WHERE tradeasset_id > 12
     * </code>
     *
     * @param     mixed $tradeassetId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function filterByTradeassetId($tradeassetId = null, $comparison = null)
    {
        if (is_array($tradeassetId)) {
            $useMinMax = false;
            if (isset($tradeassetId['min'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_TRADEASSET_ID, $tradeassetId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($tradeassetId['max'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_TRADEASSET_ID, $tradeassetId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradeAssetsTableMap::COL_TRADEASSET_ID, $tradeassetId, $comparison);
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
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function filterByTradeId($tradeId = null, $comparison = null)
    {
        if (is_array($tradeId)) {
            $useMinMax = false;
            if (isset($tradeId['min'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_TRADE_ID, $tradeId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($tradeId['max'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_TRADE_ID, $tradeId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradeAssetsTableMap::COL_TRADE_ID, $tradeId, $comparison);
    }

    /**
     * Filter the query on the player_id column
     *
     * Example usage:
     * <code>
     * $query->filterByPlayerId(1234); // WHERE player_id = 1234
     * $query->filterByPlayerId(array(12, 34)); // WHERE player_id IN (12, 34)
     * $query->filterByPlayerId(array('min' => 12)); // WHERE player_id > 12
     * </code>
     *
     * @param     mixed $playerId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function filterByPlayerId($playerId = null, $comparison = null)
    {
        if (is_array($playerId)) {
            $useMinMax = false;
            if (isset($playerId['min'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_PLAYER_ID, $playerId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($playerId['max'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_PLAYER_ID, $playerId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradeAssetsTableMap::COL_PLAYER_ID, $playerId, $comparison);
    }

    /**
     * Filter the query on the oldmanager_id column
     *
     * Example usage:
     * <code>
     * $query->filterByOldmanagerId(1234); // WHERE oldmanager_id = 1234
     * $query->filterByOldmanagerId(array(12, 34)); // WHERE oldmanager_id IN (12, 34)
     * $query->filterByOldmanagerId(array('min' => 12)); // WHERE oldmanager_id > 12
     * </code>
     *
     * @param     mixed $oldmanagerId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function filterByOldmanagerId($oldmanagerId = null, $comparison = null)
    {
        if (is_array($oldmanagerId)) {
            $useMinMax = false;
            if (isset($oldmanagerId['min'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_OLDMANAGER_ID, $oldmanagerId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($oldmanagerId['max'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_OLDMANAGER_ID, $oldmanagerId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradeAssetsTableMap::COL_OLDMANAGER_ID, $oldmanagerId, $comparison);
    }

    /**
     * Filter the query on the newmanager_id column
     *
     * Example usage:
     * <code>
     * $query->filterByNewmanagerId(1234); // WHERE newmanager_id = 1234
     * $query->filterByNewmanagerId(array(12, 34)); // WHERE newmanager_id IN (12, 34)
     * $query->filterByNewmanagerId(array('min' => 12)); // WHERE newmanager_id > 12
     * </code>
     *
     * @param     mixed $newmanagerId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function filterByNewmanagerId($newmanagerId = null, $comparison = null)
    {
        if (is_array($newmanagerId)) {
            $useMinMax = false;
            if (isset($newmanagerId['min'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_NEWMANAGER_ID, $newmanagerId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($newmanagerId['max'])) {
                $this->addUsingAlias(TradeAssetsTableMap::COL_NEWMANAGER_ID, $newmanagerId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(TradeAssetsTableMap::COL_NEWMANAGER_ID, $newmanagerId, $comparison);
    }

    /**
     * Filter the query on the was_drafted column
     *
     * Example usage:
     * <code>
     * $query->filterByWasDrafted(true); // WHERE was_drafted = true
     * $query->filterByWasDrafted('yes'); // WHERE was_drafted = true
     * </code>
     *
     * @param     boolean|string $wasDrafted The value to use as filter.
     *              Non-boolean arguments are converted using the following rules:
     *                * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *                * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     *              Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function filterByWasDrafted($wasDrafted = null, $comparison = null)
    {
        if (is_string($wasDrafted)) {
            $wasDrafted = in_array(strtolower($wasDrafted), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
        }

        return $this->addUsingAlias(TradeAssetsTableMap::COL_WAS_DRAFTED, $wasDrafted, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildTradeAssets $tradeAssets Object to remove from the list of results
     *
     * @return $this|ChildTradeAssetsQuery The current query, for fluid interface
     */
    public function prune($tradeAssets = null)
    {
        if ($tradeAssets) {
            $this->addUsingAlias(TradeAssetsTableMap::COL_TRADEASSET_ID, $tradeAssets->getTradeassetId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the trade_assets table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(TradeAssetsTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            TradeAssetsTableMap::clearInstancePool();
            TradeAssetsTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(TradeAssetsTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(TradeAssetsTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            TradeAssetsTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            TradeAssetsTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // TradeAssetsQuery
