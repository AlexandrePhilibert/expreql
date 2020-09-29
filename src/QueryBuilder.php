<?php

namespace Expreql\Expreql;

use Exception;
use PDO;
use ReflectionClass;

abstract class QueryType
{
    const SELECT = 0;
    const INSERT = 1;
    const UPDATE = 2;
    const DELETE = 3;
}

abstract class Op
{
    const or = 'OR';
    const and = 'AND';
}

class QueryBuilder
{

    private $pdo;

    private $query_type;

    private $table;

    private $fields;

    private $values;

    private $where;

    private $order_by;

    private $limit;

    private $offset;

    private $has_many;

    private $has_one;

    private $belongs_to;

    private $join;

    /**
     * @var PDOStatement The statement PDO has prepared
     */
    public $statement;

    public function __construct(int $query_type, $pdo)
    {
        $this->query_type = $query_type;
        $this->pdo = $pdo;

        return $this;
    }

    public function table(string $table)
    {
        $this->table = $table;

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
                $this->where[] = [Op::and, $args[0]];
                break;
            case 2:
            case 3:
                // Single clause e.g. where('price', 20) or ('price', '<', 50)
                $this->where[] = [Op::and, [$args]];
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
                $this->where[] = [Op::or, $args[0]];
                break;
            case 2:
            case 3:
                // Single clause e.g. where('price', 20) or ('price', '<', 50)
                $this->where[] = [Op::or, [$args]];
                break;
            default:
                throw new Exception('Unsupported number of arguments');
                break;
        }

        return $this;
    }

    /**
     * TODO: This needs to only be usable on SELECT QueryTypes
     */
    public function order_by(string $field, string $keyword)
    {
        $formatted = strtoupper($keyword);
        switch ($formatted) {
            case 'ASC':
            case 'DESC':
                $this->order_by = [$field => $formatted];
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
    public function limit(int $number): QueryBuilder
    {
        $this->limit = $number;

        return $this;
    }

    /**
     * @param int $number
     * 
     * @return QueryBuilder
     */
    public function offset(int $number): QueryBuilder
    {
        $this->offset = $number;

        return $this;
    }

    /**
     * @param $field The fields to group by
     * 
     * @return QueryBuilder
     */
    public function group_by($field)
    {
        $this->group_by = $field;

        return $this;
    }

    /**
     * @param array $fields The fields to select or update
     * 
     * @return QueryBuilder
     */
    public function fields(array $fields = null)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @param $classes
     * 
     * @return QueryBuilder
     */
    public function has_many($classes = [])
    {
        $this->has_many = $classes;

        return $this;
    }

    /**
     * @param $classes
     * 
     * @return QueryBuilder
     */
    public function has_one($classes = [])
    {
        $this->has_one = $classes;

        return $this;
    }

    /**
     * @param $classes
     * 
     * @return QueryBuilder
     */
    public function belongs_to($classes = [])
    {
        $this->belongs_to = $classes;

        return $this;
    }

    /**
     * @param $class The fully qualified class name
     * 
     * example: User::class
     * 
     * @return QueryBuilder
     */
    public function join($class)
    {
        $this->join = $class;

        return $this;
    }

    /**
     * Call this function to execute built queries
     * 
     * @return array Records from the request that was built
     */
    public function execute()
    {
        $this->build();

        if (isset($this->values)) {
            $this->statement->execute($this->values);
        } else {
            $this->statement->execute();
        }

        switch ($this->query_type) {
            case QueryType::INSERT:
            case QueryType::UPDATE:
                $stmt = $this->pdo->prepare(
                    "SELECT * FROM `" . $this->table . "` WHERE id = ?"
                );

                $stmt->execute([$this->pdo->lastInsertID()]);
                return $stmt->fetchAll(PDO::FETCH_CLASS);
            default:
                return $this->statement->fetchAll(PDO::FETCH_CLASS);
        }
    }

    /**
     * Build the query into a PDOStatment that is ready for execution
     * 
     * @return QueryBuilder
     */
    public function build()
    {
        switch ($this->query_type) {
            case QueryType::SELECT:
                $this->handle_select_building();
                break;
            case QueryType::INSERT:
                $this->handle_insert_building();
                break;
            case QueryType::UPDATE:
                $this->handle_update_building();
                break;
            case QueryType::DELETE:
                $this->handle_delete_building();
                break;
        }

        return $this;
    }

    private function handle_select_building()
    {
        $table = $this->table;

        if (isset($this->fields)) {
            $query = "SELECT";

            foreach ($this->fields as $field) {
                // Handle the fields, they could be a function call
                if (is_array($field)) {
                    $query .= " " . $field[0] . "(" . $field[1] . ")";

                    if (isset($field[2])) {
                        $query .= " AS " . $field[2];
                    }

                    $query .= ',';
                } else {
                    $query .= " $table.$field,";
                }
            }

            // Remove trailing comma
            $query = substr($query, 0, strlen($query) - 1);
        } else {
            $query = "SELECT *";
        }

        $query .= " FROM $table";

        if (isset($this->join)) {
            $query .= $this->handle_join_building();
        }

        if (isset($this->where)) {
            $query .= $this->handle_where_building();
        }

        if (isset($this->order_by)) {
            $key = key($this->order_by);
            $query .= " ORDER BY $key " . $this->order_by[$key];
        }

        if (isset($this->group_by)) {
            $query .= " GROUP BY " . $this->group_by;
        }

        if (isset($this->limit)) {
            $query .= " LIMIT " . $this->limit;
        }

        if (isset($this->offset)) {
            $query .= " OFFSET " . $this->offset;
        }

        $this->statement = $this->pdo->prepare($query);
    }

    /**
     * TODO(alexandre): Should we validate the operator used inside conditions ?
     * 
     * @return string
     */
    private function handle_where_building(): string
    {
        $nb_clauses = count($this->where);
        $where = ' WHERE ';

        // A segment is an array of conditions with the first index being the 
        // operator joining two clauses such as `Op::and` or `Op::or`
        foreach ($this->where as $segment) {
            $nb_conditions = count($segment[1]);
            if ($nb_conditions > 1 && $nb_clauses > 1) {
                $where .= "(";
            }

            foreach ($segment[1] as $condition) {
                $length = count($condition);
                if ($length == 2) {
                    // The condition has no operator in it, default to `=`
                    $where .= $condition[0] . " = ? " . $segment[0] . " ";
                    $this->values[] = $condition[1];
                } else if ($length == 3) {
                    // The condition has an operator, use the one specified
                    $where .= $condition[0] . " " . $condition[1] . " ? " . $segment[0] . " ";
                    $this->values[] = $condition[2];
                }
            }
            // Remove trailing operator
            $where = substr($where, 0, strlen($where) - strlen($segment[0]) - 2);

            if ($nb_conditions > 1 && $nb_clauses > 1) {
                $where .= ")";
            }

            $where .= " " . $segment[0] . " ";
        }
        // Remove trailing operator
        $where = substr($where, 0, strlen($where) - strlen($segment[0]) - 2);

        return $where;
    }

    private function handle_join_building()
    {
        $table = $this->table;

        $join_class = new ReflectionClass($this->join);
        $join_table = $join_class->getStaticPropertyValue('table');
        $join_primary_key = $join_class->getStaticPropertyValue('primary_key');

        // has many relation
        if (array_key_exists($this->join, $this->has_many)) {
            $join_field = $this->has_many[$this->join];
            // TODO: Is there a better way to do this ?
            return " LEFT JOIN $join_table ON $table.$join_primary_key = $join_table.$join_field";
        }

        // has one relation
        if (in_array($this->join, $this->has_one)) {
            // $join_field = $this->has_one[$this->join];
            return " INNER JOIN $join_table ON $table.exercises_id = $join_table.$join_primary_key";
        }

        if (array_key_exists($this->join, $this->belongs_to)) {
            $join_field = $this->belongs_to[$this->join];
            return " LEFT JOIN $join_table ON $table.$join_field = $join_table.$join_primary_key";
        }
    }

    private function handle_insert_building()
    {
        $table = $this->table;

        $prepare  = str_repeat('?,', count($this->fields) - 1) . '?';
        $keys = [];
        $values = [];

        foreach ($this->fields as $key => $value) {
            $keys[] = $key;
            $values[] = $value;
        }

        $this->values = $values;
        $this->statement = $this->pdo->prepare("INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES ($prepare)");
    }

    private function handle_update_building()
    {
        $table = $this->table;
        $query = "UPDATE $table SET";

        foreach ($this->fields as $key => $value) {
            $this->values[] = $value;
            $query .=  " $key = ?,";
        }

        $query = substr($query, 0, strlen($query) - 1);

        if (isset($this->where)) {
            $query .= $this->handle_where_building();
        }

        $this->statement = $this->pdo->prepare($query);
    }

    private function handle_delete_building()
    {
        $table = $this->table;
        $query = "DELETE FROM $table";

        if (isset($this->where)) {
            $query .= $this->handle_where_building();
        }

        $this->statement = $this->pdo->prepare($query);
    }
}
