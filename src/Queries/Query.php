<?php

namespace Expreql\Expreql;

use PDOStatement;
use ReflectionClass;

abstract class Query
{

    /**
     * The SQL table name of the SQL query
     * 
     * This property is used when building queries with the QueryBuilder without
     * using any models
     * 
     * @var string
     */
    public string $table;

    /**
     * The base model of the SQL query
     * 
     * @var string
     */
    public string $base_model;

    /**
     * The SQL statement that will be built from the properties set.
     * 
     * @var string
     */
    public string $statement;

    /**
     * The where clause of the SQL statement being built
     * 
     * @var array
     */
    public array $where;

    /**
     * The joined models of the SQL statemetn being built
     * 
     * The models are stored as fully qualified class name
     * 
     * @var array
     */
    public array $joins = [];

    abstract public function build(): PDOStatement;

    abstract public function execute();

    protected function build_where_clause(): string
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

    protected function build_join_clause(): string
    {
        $table = $this->get_base_table_name();
        $join_statement = "";

        foreach ($this->joins as $join) {
            $join_class = new ReflectionClass($join);
            $join_table = $join_class->getStaticPropertyValue('table');
            $join_primary_key = $join_class->getStaticPropertyValue('primary_key');

            if (array_key_exists($join, $this->base_model::$has_many)) {
                $join_field = $this->base_model::$has_many[$join];
                $join_statement .= " LEFT JOIN $join_table ON $table.$join_primary_key = $join_table.$join_field";
            } else if (array_key_exists($join, $this->base_model::$has_one)) {
                $join_field = $this->base_model::$primary_key;
                $join_statement .= " INNER JOIN $join_table ON $table.$join_field = $join_table.$join_primary_key";
            } else if (array_key_exists($join, $this->base_model::$belongs_to)) {
                $join_field = $this->base_model::$belongs_to[$join];
                $join_statement .= " LEFT JOIN $join_table ON $table.$join_field = $join_table.$join_primary_key";
            }
        }

        return $join_statement;
    }

    /**
     * Get the base table name
     * 
     * @return string The base table name
     */
    protected function get_base_table_name(): string
    {
        if (isset($this->table)) {
            return $this->table;
        } 
        return $this->base_model::$table;
    }
}
