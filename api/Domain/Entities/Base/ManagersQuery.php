<?php

namespace Base;

use \Managers as ChildManagers;
use \ManagersQuery as ChildManagersQuery;
use \Exception;
use \PDO;
use Map\ManagersTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'managers' table.
 *
 *
 *
 * @method     ChildManagersQuery orderByManagerId($order = Criteria::ASC) Order by the manager_id column
 * @method     ChildManagersQuery orderByDraftId($order = Criteria::ASC) Order by the draft_id column
 * @method     ChildManagersQuery orderByManagerName($order = Criteria::ASC) Order by the manager_name column
 * @method     ChildManagersQuery orderByManagerEmail($order = Criteria::ASC) Order by the manager_email column
 * @method     ChildManagersQuery orderByDraftOrder($order = Criteria::ASC) Order by the draft_order column
 *
 * @method     ChildManagersQuery groupByManagerId() Group by the manager_id column
 * @method     ChildManagersQuery groupByDraftId() Group by the draft_id column
 * @method     ChildManagersQuery groupByManagerName() Group by the manager_name column
 * @method     ChildManagersQuery groupByManagerEmail() Group by the manager_email column
 * @method     ChildManagersQuery groupByDraftOrder() Group by the draft_order column
 *
 * @method     ChildManagersQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildManagersQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildManagersQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildManagers findOne(ConnectionInterface $con = null) Return the first ChildManagers matching the query
 * @method     ChildManagers findOneOrCreate(ConnectionInterface $con = null) Return the first ChildManagers matching the query, or a new ChildManagers object populated from the query conditions when no match is found
 *
 * @method     ChildManagers findOneByManagerId(int $manager_id) Return the first ChildManagers filtered by the manager_id column
 * @method     ChildManagers findOneByDraftId(int $draft_id) Return the first ChildManagers filtered by the draft_id column
 * @method     ChildManagers findOneByManagerName(string $manager_name) Return the first ChildManagers filtered by the manager_name column
 * @method     ChildManagers findOneByManagerEmail(string $manager_email) Return the first ChildManagers filtered by the manager_email column
 * @method     ChildManagers findOneByDraftOrder(int $draft_order) Return the first ChildManagers filtered by the draft_order column *

 * @method     ChildManagers requirePk($key, ConnectionInterface $con = null) Return the ChildManagers by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildManagers requireOne(ConnectionInterface $con = null) Return the first ChildManagers matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildManagers requireOneByManagerId(int $manager_id) Return the first ChildManagers filtered by the manager_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildManagers requireOneByDraftId(int $draft_id) Return the first ChildManagers filtered by the draft_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildManagers requireOneByManagerName(string $manager_name) Return the first ChildManagers filtered by the manager_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildManagers requireOneByManagerEmail(string $manager_email) Return the first ChildManagers filtered by the manager_email column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildManagers requireOneByDraftOrder(int $draft_order) Return the first ChildManagers filtered by the draft_order column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildManagers[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildManagers objects based on current ModelCriteria
 * @method     ChildManagers[]|ObjectCollection findByManagerId(int $manager_id) Return ChildManagers objects filtered by the manager_id column
 * @method     ChildManagers[]|ObjectCollection findByDraftId(int $draft_id) Return ChildManagers objects filtered by the draft_id column
 * @method     ChildManagers[]|ObjectCollection findByManagerName(string $manager_name) Return ChildManagers objects filtered by the manager_name column
 * @method     ChildManagers[]|ObjectCollection findByManagerEmail(string $manager_email) Return ChildManagers objects filtered by the manager_email column
 * @method     ChildManagers[]|ObjectCollection findByDraftOrder(int $draft_order) Return ChildManagers objects filtered by the draft_order column
 * @method     ChildManagers[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class ManagersQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \Base\ManagersQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'phpdraft', $modelName = '\\Managers', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildManagersQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildManagersQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildManagersQuery) {
            return $criteria;
        }
        $query = new ChildManagersQuery();
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
     * @return ChildManagers|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = ManagersTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(ManagersTableMap::DATABASE_NAME);
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
     * @return ChildManagers A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT manager_id, draft_id, manager_name, manager_email, draft_order FROM managers WHERE manager_id = :p0';
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
            /** @var ChildManagers $obj */
            $obj = new ChildManagers();
            $obj->hydrate($row);
            ManagersTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildManagers|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildManagersQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(ManagersTableMap::COL_MANAGER_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildManagersQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(ManagersTableMap::COL_MANAGER_ID, $keys, Criteria::IN);
    }

    /**
     * Filter the query on the manager_id column
     *
     * Example usage:
     * <code>
     * $query->filterByManagerId(1234); // WHERE manager_id = 1234
     * $query->filterByManagerId(array(12, 34)); // WHERE manager_id IN (12, 34)
     * $query->filterByManagerId(array('min' => 12)); // WHERE manager_id > 12
     * </code>
     *
     * @param     mixed $managerId The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildManagersQuery The current query, for fluid interface
     */
    public function filterByManagerId($managerId = null, $comparison = null)
    {
        if (is_array($managerId)) {
            $useMinMax = false;
            if (isset($managerId['min'])) {
                $this->addUsingAlias(ManagersTableMap::COL_MANAGER_ID, $managerId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($managerId['max'])) {
                $this->addUsingAlias(ManagersTableMap::COL_MANAGER_ID, $managerId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ManagersTableMap::COL_MANAGER_ID, $managerId, $comparison);
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
     * @return $this|ChildManagersQuery The current query, for fluid interface
     */
    public function filterByDraftId($draftId = null, $comparison = null)
    {
        if (is_array($draftId)) {
            $useMinMax = false;
            if (isset($draftId['min'])) {
                $this->addUsingAlias(ManagersTableMap::COL_DRAFT_ID, $draftId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftId['max'])) {
                $this->addUsingAlias(ManagersTableMap::COL_DRAFT_ID, $draftId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ManagersTableMap::COL_DRAFT_ID, $draftId, $comparison);
    }

    /**
     * Filter the query on the manager_name column
     *
     * Example usage:
     * <code>
     * $query->filterByManagerName('fooValue');   // WHERE manager_name = 'fooValue'
     * $query->filterByManagerName('%fooValue%'); // WHERE manager_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $managerName The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildManagersQuery The current query, for fluid interface
     */
    public function filterByManagerName($managerName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($managerName)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $managerName)) {
                $managerName = str_replace('*', '%', $managerName);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ManagersTableMap::COL_MANAGER_NAME, $managerName, $comparison);
    }

    /**
     * Filter the query on the manager_email column
     *
     * Example usage:
     * <code>
     * $query->filterByManagerEmail('fooValue');   // WHERE manager_email = 'fooValue'
     * $query->filterByManagerEmail('%fooValue%'); // WHERE manager_email LIKE '%fooValue%'
     * </code>
     *
     * @param     string $managerEmail The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildManagersQuery The current query, for fluid interface
     */
    public function filterByManagerEmail($managerEmail = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($managerEmail)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $managerEmail)) {
                $managerEmail = str_replace('*', '%', $managerEmail);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(ManagersTableMap::COL_MANAGER_EMAIL, $managerEmail, $comparison);
    }

    /**
     * Filter the query on the draft_order column
     *
     * Example usage:
     * <code>
     * $query->filterByDraftOrder(1234); // WHERE draft_order = 1234
     * $query->filterByDraftOrder(array(12, 34)); // WHERE draft_order IN (12, 34)
     * $query->filterByDraftOrder(array('min' => 12)); // WHERE draft_order > 12
     * </code>
     *
     * @param     mixed $draftOrder The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildManagersQuery The current query, for fluid interface
     */
    public function filterByDraftOrder($draftOrder = null, $comparison = null)
    {
        if (is_array($draftOrder)) {
            $useMinMax = false;
            if (isset($draftOrder['min'])) {
                $this->addUsingAlias(ManagersTableMap::COL_DRAFT_ORDER, $draftOrder['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftOrder['max'])) {
                $this->addUsingAlias(ManagersTableMap::COL_DRAFT_ORDER, $draftOrder['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(ManagersTableMap::COL_DRAFT_ORDER, $draftOrder, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildManagers $managers Object to remove from the list of results
     *
     * @return $this|ChildManagersQuery The current query, for fluid interface
     */
    public function prune($managers = null)
    {
        if ($managers) {
            $this->addUsingAlias(ManagersTableMap::COL_MANAGER_ID, $managers->getManagerId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the managers table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(ManagersTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            ManagersTableMap::clearInstancePool();
            ManagersTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(ManagersTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(ManagersTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            ManagersTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            ManagersTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // ManagersQuery
