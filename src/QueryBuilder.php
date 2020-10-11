<?php

namespace Expreql\Expreql;

use Exception;
use PDO;
use PDOStatement;

abstract class Op
{
    const or = 'OR';
    const and = 'AND';
}

class QueryBuilder
{

    /**
     * @var Query The query being built
     */
    private Query $query;

    /**
     * @var PDOStatement The statement PDO has prepared
     */
    public $statement;

    /**
     * @param string $query
     * @param PDO $connection
     * 
     * @return QueryBuilder
     */
    public function __construct(string $query, PDO $connection)
    {
        $this->query = new $query();
        $this->query->connection = $connection;

        return $this;
    }

    public function base_model(string $base_model)
    {
        $this->query->base_model = $base_model;

        return $this;
    }

    public function table(string $table)
    {
        $this->query->table = $table;

        return $this;
    }

    /**
     * @param array ...$args
     * 
     * @return QueryBuilder
     */
    public function where(...$args)
    {
        $length = count($args);

        switch ($length) {
            case 1:
                if (!is_array($args)) {
                    throw new Exception('Invalid where syntax, argument must be an array');
                }
                $this->query->where[] = [Op::and, $args[0]];
                break;
            case 2:
            case 3:
                // Single clause e.g. where('price', 20) or ('price', '<', 50)
                $this->query->where[] = [Op::and, [$args]];
                break;
            default:
                throw new Exception('Unsupported number of arguments');
                break;
        }

        return $this;
    }

    public function where_or(...$args): QueryBuilder
    {
        $length = count($args);

        switch ($length) {
            case 1:
                if (!is_array($args)) {
                    throw new Exception('Invalid where syntax, argument must be an array');
                }
                $this->query->where[] = [Op::or, $args[0]];
                break;
            case 2:
            case 3:
                // Single clause e.g. where('price', 20) or ('price', '<', 50)
                $this->query->where[] = [Op::or, [$args]];
                break;
            default:
                throw new Exception('Unsupported number of arguments');
                break;
        }

        return $this;
    }


    /**
     * @param string $field
     * @param string $keyword   ASC,DESC
     * 
     * @return QueryBuilder
     */
    public function order_by(string $field, string $keyword)
    {
        $formatted = strtoupper($keyword);
        switch ($formatted) {
            case 'ASC':
            case 'DESC':
                $this->query->order_by = [$field => $formatted];
                break;
            default:
                throw new Exception("order by keyword not supported");
        }

        return $this;
    }

    /**
     * @param int $number
     * 
     * @return QueryBuilder
     */
    public function limit(int $limit): QueryBuilder
    {
        $this->query->limit = $limit;

        return $this;
    }

    /**
     * @param int $number
     * 
     * @return QueryBuilder
     */
    public function offset(int $offset): QueryBuilder
    {
        $this->query->offset = $offset;

        return $this;
    }

    /**
     * @param $field The fields to group by
     * 
     * @return QueryBuilder
     */
    public function group_by($field)
    {
        $this->query->group_by = $field;

        return $this;
    }

    /**
     * @param array $fields The fields to select or update
     * 
     * @return QueryBuilder
     */
    public function fields(array $fields = null)
    {
        $this->query->fields = $fields;

        return $this;
    }

    /**
     * @param Model|array class The fully qualified class name
     * 
     * example: User::class
     * 
     * @return QueryBuilder
     */
    public function join($class)
    {
        if (is_array($class)) {
            $this->query->joins = $class;
        } else {
            $this->query->joins = [$class];
        }

        return $this;
    }


    public function build(): PDOStatement
    {
        return $this->query->build();
    }

    /**
     * Call this function to execute built queries
     * 
     * @return ArrayObject|int Records from the request that was built
     */
    public function execute()
    {
        return $this->query->execute();
    }
}
