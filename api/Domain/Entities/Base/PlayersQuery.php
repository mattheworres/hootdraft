<?php

namespace Base;

use \Players as ChildPlayers;
use \PlayersQuery as ChildPlayersQuery;
use \Exception;
use \PDO;
use Map\PlayersTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;

/**
 * Base class that represents a query for the 'players' table.
 *
 *
 *
 * @method     ChildPlayersQuery orderByPlayerId($order = Criteria::ASC) Order by the player_id column
 * @method     ChildPlayersQuery orderByManagerId($order = Criteria::ASC) Order by the manager_id column
 * @method     ChildPlayersQuery orderByFirstName($order = Criteria::ASC) Order by the first_name column
 * @method     ChildPlayersQuery orderByLastName($order = Criteria::ASC) Order by the last_name column
 * @method     ChildPlayersQuery orderByTeam($order = Criteria::ASC) Order by the team column
 * @method     ChildPlayersQuery orderByPosition($order = Criteria::ASC) Order by the position column
 * @method     ChildPlayersQuery orderByPickTime($order = Criteria::ASC) Order by the pick_time column
 * @method     ChildPlayersQuery orderByPickDuration($order = Criteria::ASC) Order by the pick_duration column
 * @method     ChildPlayersQuery orderByPlayerCounter($order = Criteria::ASC) Order by the player_counter column
 * @method     ChildPlayersQuery orderByDraftId($order = Criteria::ASC) Order by the draft_id column
 * @method     ChildPlayersQuery orderByPlayerRound($order = Criteria::ASC) Order by the player_round column
 * @method     ChildPlayersQuery orderByPlayerPick($order = Criteria::ASC) Order by the player_pick column
 *
 * @method     ChildPlayersQuery groupByPlayerId() Group by the player_id column
 * @method     ChildPlayersQuery groupByManagerId() Group by the manager_id column
 * @method     ChildPlayersQuery groupByFirstName() Group by the first_name column
 * @method     ChildPlayersQuery groupByLastName() Group by the last_name column
 * @method     ChildPlayersQuery groupByTeam() Group by the team column
 * @method     ChildPlayersQuery groupByPosition() Group by the position column
 * @method     ChildPlayersQuery groupByPickTime() Group by the pick_time column
 * @method     ChildPlayersQuery groupByPickDuration() Group by the pick_duration column
 * @method     ChildPlayersQuery groupByPlayerCounter() Group by the player_counter column
 * @method     ChildPlayersQuery groupByDraftId() Group by the draft_id column
 * @method     ChildPlayersQuery groupByPlayerRound() Group by the player_round column
 * @method     ChildPlayersQuery groupByPlayerPick() Group by the player_pick column
 *
 * @method     ChildPlayersQuery leftJoin($relation) Adds a LEFT JOIN clause to the query
 * @method     ChildPlayersQuery rightJoin($relation) Adds a RIGHT JOIN clause to the query
 * @method     ChildPlayersQuery innerJoin($relation) Adds a INNER JOIN clause to the query
 *
 * @method     ChildPlayers findOne(ConnectionInterface $con = null) Return the first ChildPlayers matching the query
 * @method     ChildPlayers findOneOrCreate(ConnectionInterface $con = null) Return the first ChildPlayers matching the query, or a new ChildPlayers object populated from the query conditions when no match is found
 *
 * @method     ChildPlayers findOneByPlayerId(int $player_id) Return the first ChildPlayers filtered by the player_id column
 * @method     ChildPlayers findOneByManagerId(int $manager_id) Return the first ChildPlayers filtered by the manager_id column
 * @method     ChildPlayers findOneByFirstName(string $first_name) Return the first ChildPlayers filtered by the first_name column
 * @method     ChildPlayers findOneByLastName(string $last_name) Return the first ChildPlayers filtered by the last_name column
 * @method     ChildPlayers findOneByTeam(string $team) Return the first ChildPlayers filtered by the team column
 * @method     ChildPlayers findOneByPosition(string $position) Return the first ChildPlayers filtered by the position column
 * @method     ChildPlayers findOneByPickTime(string $pick_time) Return the first ChildPlayers filtered by the pick_time column
 * @method     ChildPlayers findOneByPickDuration(int $pick_duration) Return the first ChildPlayers filtered by the pick_duration column
 * @method     ChildPlayers findOneByPlayerCounter(int $player_counter) Return the first ChildPlayers filtered by the player_counter column
 * @method     ChildPlayers findOneByDraftId(int $draft_id) Return the first ChildPlayers filtered by the draft_id column
 * @method     ChildPlayers findOneByPlayerRound(int $player_round) Return the first ChildPlayers filtered by the player_round column
 * @method     ChildPlayers findOneByPlayerPick(int $player_pick) Return the first ChildPlayers filtered by the player_pick column *

 * @method     ChildPlayers requirePk($key, ConnectionInterface $con = null) Return the ChildPlayers by primary key and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOne(ConnectionInterface $con = null) Return the first ChildPlayers matching the query and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildPlayers requireOneByPlayerId(int $player_id) Return the first ChildPlayers filtered by the player_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByManagerId(int $manager_id) Return the first ChildPlayers filtered by the manager_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByFirstName(string $first_name) Return the first ChildPlayers filtered by the first_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByLastName(string $last_name) Return the first ChildPlayers filtered by the last_name column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByTeam(string $team) Return the first ChildPlayers filtered by the team column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByPosition(string $position) Return the first ChildPlayers filtered by the position column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByPickTime(string $pick_time) Return the first ChildPlayers filtered by the pick_time column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByPickDuration(int $pick_duration) Return the first ChildPlayers filtered by the pick_duration column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByPlayerCounter(int $player_counter) Return the first ChildPlayers filtered by the player_counter column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByDraftId(int $draft_id) Return the first ChildPlayers filtered by the draft_id column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByPlayerRound(int $player_round) Return the first ChildPlayers filtered by the player_round column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 * @method     ChildPlayers requireOneByPlayerPick(int $player_pick) Return the first ChildPlayers filtered by the player_pick column and throws \Propel\Runtime\Exception\EntityNotFoundException when not found
 *
 * @method     ChildPlayers[]|ObjectCollection find(ConnectionInterface $con = null) Return ChildPlayers objects based on current ModelCriteria
 * @method     ChildPlayers[]|ObjectCollection findByPlayerId(int $player_id) Return ChildPlayers objects filtered by the player_id column
 * @method     ChildPlayers[]|ObjectCollection findByManagerId(int $manager_id) Return ChildPlayers objects filtered by the manager_id column
 * @method     ChildPlayers[]|ObjectCollection findByFirstName(string $first_name) Return ChildPlayers objects filtered by the first_name column
 * @method     ChildPlayers[]|ObjectCollection findByLastName(string $last_name) Return ChildPlayers objects filtered by the last_name column
 * @method     ChildPlayers[]|ObjectCollection findByTeam(string $team) Return ChildPlayers objects filtered by the team column
 * @method     ChildPlayers[]|ObjectCollection findByPosition(string $position) Return ChildPlayers objects filtered by the position column
 * @method     ChildPlayers[]|ObjectCollection findByPickTime(string $pick_time) Return ChildPlayers objects filtered by the pick_time column
 * @method     ChildPlayers[]|ObjectCollection findByPickDuration(int $pick_duration) Return ChildPlayers objects filtered by the pick_duration column
 * @method     ChildPlayers[]|ObjectCollection findByPlayerCounter(int $player_counter) Return ChildPlayers objects filtered by the player_counter column
 * @method     ChildPlayers[]|ObjectCollection findByDraftId(int $draft_id) Return ChildPlayers objects filtered by the draft_id column
 * @method     ChildPlayers[]|ObjectCollection findByPlayerRound(int $player_round) Return ChildPlayers objects filtered by the player_round column
 * @method     ChildPlayers[]|ObjectCollection findByPlayerPick(int $player_pick) Return ChildPlayers objects filtered by the player_pick column
 * @method     ChildPlayers[]|\Propel\Runtime\Util\PropelModelPager paginate($page = 1, $maxPerPage = 10, ConnectionInterface $con = null) Issue a SELECT query based on the current ModelCriteria and uses a page and a maximum number of results per page to compute an offset and a limit
 *
 */
abstract class PlayersQuery extends ModelCriteria
{
    protected $entityNotFoundExceptionClass = '\\Propel\\Runtime\\Exception\\EntityNotFoundException';

    /**
     * Initializes internal state of \Base\PlayersQuery object.
     *
     * @param     string $dbName The database name
     * @param     string $modelName The phpName of a model, e.g. 'Book'
     * @param     string $modelAlias The alias for the model in this query, e.g. 'b'
     */
    public function __construct($dbName = 'phpdraft', $modelName = '\\Players', $modelAlias = null)
    {
        parent::__construct($dbName, $modelName, $modelAlias);
    }

    /**
     * Returns a new ChildPlayersQuery object.
     *
     * @param     string $modelAlias The alias of a model in the query
     * @param     Criteria $criteria Optional Criteria to build the query from
     *
     * @return ChildPlayersQuery
     */
    public static function create($modelAlias = null, Criteria $criteria = null)
    {
        if ($criteria instanceof ChildPlayersQuery) {
            return $criteria;
        }
        $query = new ChildPlayersQuery();
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
     * @return ChildPlayers|array|mixed the result, formatted by the current formatter
     */
    public function findPk($key, ConnectionInterface $con = null)
    {
        if ($key === null) {
            return null;
        }
        if ((null !== ($obj = PlayersTableMap::getInstanceFromPool((string) $key))) && !$this->formatter) {
            // the object is already in the instance pool
            return $obj;
        }
        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(PlayersTableMap::DATABASE_NAME);
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
     * @return ChildPlayers A model object, or null if the key is not found
     */
    protected function findPkSimple($key, ConnectionInterface $con)
    {
        $sql = 'SELECT player_id, manager_id, first_name, last_name, team, position, pick_time, pick_duration, player_counter, draft_id, player_round, player_pick FROM players WHERE player_id = :p0';
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
            /** @var ChildPlayers $obj */
            $obj = new ChildPlayers();
            $obj->hydrate($row);
            PlayersTableMap::addInstanceToPool($obj, (string) $key);
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
     * @return ChildPlayers|array|mixed the result, formatted by the current formatter
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
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPrimaryKey($key)
    {

        return $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ID, $key, Criteria::EQUAL);
    }

    /**
     * Filter the query by a list of primary keys
     *
     * @param     array $keys The list of primary key to use for the query
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPrimaryKeys($keys)
    {

        return $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ID, $keys, Criteria::IN);
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
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPlayerId($playerId = null, $comparison = null)
    {
        if (is_array($playerId)) {
            $useMinMax = false;
            if (isset($playerId['min'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ID, $playerId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($playerId['max'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ID, $playerId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ID, $playerId, $comparison);
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
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByManagerId($managerId = null, $comparison = null)
    {
        if (is_array($managerId)) {
            $useMinMax = false;
            if (isset($managerId['min'])) {
                $this->addUsingAlias(PlayersTableMap::COL_MANAGER_ID, $managerId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($managerId['max'])) {
                $this->addUsingAlias(PlayersTableMap::COL_MANAGER_ID, $managerId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_MANAGER_ID, $managerId, $comparison);
    }

    /**
     * Filter the query on the first_name column
     *
     * Example usage:
     * <code>
     * $query->filterByFirstName('fooValue');   // WHERE first_name = 'fooValue'
     * $query->filterByFirstName('%fooValue%'); // WHERE first_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $firstName The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByFirstName($firstName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($firstName)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $firstName)) {
                $firstName = str_replace('*', '%', $firstName);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_FIRST_NAME, $firstName, $comparison);
    }

    /**
     * Filter the query on the last_name column
     *
     * Example usage:
     * <code>
     * $query->filterByLastName('fooValue');   // WHERE last_name = 'fooValue'
     * $query->filterByLastName('%fooValue%'); // WHERE last_name LIKE '%fooValue%'
     * </code>
     *
     * @param     string $lastName The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByLastName($lastName = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($lastName)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $lastName)) {
                $lastName = str_replace('*', '%', $lastName);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_LAST_NAME, $lastName, $comparison);
    }

    /**
     * Filter the query on the team column
     *
     * Example usage:
     * <code>
     * $query->filterByTeam('fooValue');   // WHERE team = 'fooValue'
     * $query->filterByTeam('%fooValue%'); // WHERE team LIKE '%fooValue%'
     * </code>
     *
     * @param     string $team The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByTeam($team = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($team)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $team)) {
                $team = str_replace('*', '%', $team);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_TEAM, $team, $comparison);
    }

    /**
     * Filter the query on the position column
     *
     * Example usage:
     * <code>
     * $query->filterByPosition('fooValue');   // WHERE position = 'fooValue'
     * $query->filterByPosition('%fooValue%'); // WHERE position LIKE '%fooValue%'
     * </code>
     *
     * @param     string $position The value to use as filter.
     *              Accepts wildcards (* and % trigger a LIKE)
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPosition($position = null, $comparison = null)
    {
        if (null === $comparison) {
            if (is_array($position)) {
                $comparison = Criteria::IN;
            } elseif (preg_match('/[\%\*]/', $position)) {
                $position = str_replace('*', '%', $position);
                $comparison = Criteria::LIKE;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_POSITION, $position, $comparison);
    }

    /**
     * Filter the query on the pick_time column
     *
     * Example usage:
     * <code>
     * $query->filterByPickTime('2011-03-14'); // WHERE pick_time = '2011-03-14'
     * $query->filterByPickTime('now'); // WHERE pick_time = '2011-03-14'
     * $query->filterByPickTime(array('max' => 'yesterday')); // WHERE pick_time > '2011-03-13'
     * </code>
     *
     * @param     mixed $pickTime The value to use as filter.
     *              Values can be integers (unix timestamps), DateTime objects, or strings.
     *              Empty strings are treated as NULL.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPickTime($pickTime = null, $comparison = null)
    {
        if (is_array($pickTime)) {
            $useMinMax = false;
            if (isset($pickTime['min'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PICK_TIME, $pickTime['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($pickTime['max'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PICK_TIME, $pickTime['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_PICK_TIME, $pickTime, $comparison);
    }

    /**
     * Filter the query on the pick_duration column
     *
     * Example usage:
     * <code>
     * $query->filterByPickDuration(1234); // WHERE pick_duration = 1234
     * $query->filterByPickDuration(array(12, 34)); // WHERE pick_duration IN (12, 34)
     * $query->filterByPickDuration(array('min' => 12)); // WHERE pick_duration > 12
     * </code>
     *
     * @param     mixed $pickDuration The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPickDuration($pickDuration = null, $comparison = null)
    {
        if (is_array($pickDuration)) {
            $useMinMax = false;
            if (isset($pickDuration['min'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PICK_DURATION, $pickDuration['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($pickDuration['max'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PICK_DURATION, $pickDuration['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_PICK_DURATION, $pickDuration, $comparison);
    }

    /**
     * Filter the query on the player_counter column
     *
     * Example usage:
     * <code>
     * $query->filterByPlayerCounter(1234); // WHERE player_counter = 1234
     * $query->filterByPlayerCounter(array(12, 34)); // WHERE player_counter IN (12, 34)
     * $query->filterByPlayerCounter(array('min' => 12)); // WHERE player_counter > 12
     * </code>
     *
     * @param     mixed $playerCounter The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPlayerCounter($playerCounter = null, $comparison = null)
    {
        if (is_array($playerCounter)) {
            $useMinMax = false;
            if (isset($playerCounter['min'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PLAYER_COUNTER, $playerCounter['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($playerCounter['max'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PLAYER_COUNTER, $playerCounter['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_PLAYER_COUNTER, $playerCounter, $comparison);
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
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByDraftId($draftId = null, $comparison = null)
    {
        if (is_array($draftId)) {
            $useMinMax = false;
            if (isset($draftId['min'])) {
                $this->addUsingAlias(PlayersTableMap::COL_DRAFT_ID, $draftId['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($draftId['max'])) {
                $this->addUsingAlias(PlayersTableMap::COL_DRAFT_ID, $draftId['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_DRAFT_ID, $draftId, $comparison);
    }

    /**
     * Filter the query on the player_round column
     *
     * Example usage:
     * <code>
     * $query->filterByPlayerRound(1234); // WHERE player_round = 1234
     * $query->filterByPlayerRound(array(12, 34)); // WHERE player_round IN (12, 34)
     * $query->filterByPlayerRound(array('min' => 12)); // WHERE player_round > 12
     * </code>
     *
     * @param     mixed $playerRound The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPlayerRound($playerRound = null, $comparison = null)
    {
        if (is_array($playerRound)) {
            $useMinMax = false;
            if (isset($playerRound['min'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ROUND, $playerRound['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($playerRound['max'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ROUND, $playerRound['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ROUND, $playerRound, $comparison);
    }

    /**
     * Filter the query on the player_pick column
     *
     * Example usage:
     * <code>
     * $query->filterByPlayerPick(1234); // WHERE player_pick = 1234
     * $query->filterByPlayerPick(array(12, 34)); // WHERE player_pick IN (12, 34)
     * $query->filterByPlayerPick(array('min' => 12)); // WHERE player_pick > 12
     * </code>
     *
     * @param     mixed $playerPick The value to use as filter.
     *              Use scalar values for equality.
     *              Use array values for in_array() equivalent.
     *              Use associative array('min' => $minValue, 'max' => $maxValue) for intervals.
     * @param     string $comparison Operator to use for the column comparison, defaults to Criteria::EQUAL
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function filterByPlayerPick($playerPick = null, $comparison = null)
    {
        if (is_array($playerPick)) {
            $useMinMax = false;
            if (isset($playerPick['min'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PLAYER_PICK, $playerPick['min'], Criteria::GREATER_EQUAL);
                $useMinMax = true;
            }
            if (isset($playerPick['max'])) {
                $this->addUsingAlias(PlayersTableMap::COL_PLAYER_PICK, $playerPick['max'], Criteria::LESS_EQUAL);
                $useMinMax = true;
            }
            if ($useMinMax) {
                return $this;
            }
            if (null === $comparison) {
                $comparison = Criteria::IN;
            }
        }

        return $this->addUsingAlias(PlayersTableMap::COL_PLAYER_PICK, $playerPick, $comparison);
    }

    /**
     * Exclude object from result
     *
     * @param   ChildPlayers $players Object to remove from the list of results
     *
     * @return $this|ChildPlayersQuery The current query, for fluid interface
     */
    public function prune($players = null)
    {
        if ($players) {
            $this->addUsingAlias(PlayersTableMap::COL_PLAYER_ID, $players->getPlayerId(), Criteria::NOT_EQUAL);
        }

        return $this;
    }

    /**
     * Deletes all rows from the players table.
     *
     * @param ConnectionInterface $con the connection to use
     * @return int The number of affected rows (if supported by underlying database driver).
     */
    public function doDeleteAll(ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(PlayersTableMap::DATABASE_NAME);
        }

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con) {
            $affectedRows = 0; // initialize var to track total num of affected rows
            $affectedRows += parent::doDeleteAll($con);
            // Because this db requires some delete cascade/set null emulation, we have to
            // clear the cached instance *after* the emulation has happened (since
            // instances get re-added by the select statement contained therein).
            PlayersTableMap::clearInstancePool();
            PlayersTableMap::clearRelatedInstancePool();

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
            $con = Propel::getServiceContainer()->getWriteConnection(PlayersTableMap::DATABASE_NAME);
        }

        $criteria = $this;

        // Set the correct dbName
        $criteria->setDbName(PlayersTableMap::DATABASE_NAME);

        // use transaction because $criteria could contain info
        // for more than one table or we could emulating ON DELETE CASCADE, etc.
        return $con->transaction(function () use ($con, $criteria) {
            $affectedRows = 0; // initialize var to track total num of affected rows

            PlayersTableMap::removeInstanceFromPool($criteria);

            $affectedRows += ModelCriteria::delete($con);
            PlayersTableMap::clearRelatedInstancePool();

            return $affectedRows;
        });
    }

} // PlayersQuery
