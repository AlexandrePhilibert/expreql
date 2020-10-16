<?php

namespace Expreql\Expreql;

use PDOStatement;

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

    /**
     * Create a join statement using the base model to get the relation relative
     * to the join model
     * 
     * @param string $base_model  The model on which to join the join model
     * @param string $join_model  The model to join on the base model
     * 
     * @return string
     */
    private function create_join_statement(string $base_model, string $join_model): string
    {
        $base_table = $base_model::$table;
        $base_pk = $base_model::$primary_key;
        $join_table = $join_model::$table;
        $join_pk = $this->get_foreign_key($base_model, $join_model);

        return " LEFT JOIN $join_table ON $base_table.$base_pk = $join_table.$join_pk";
    }

    /**
     * Build the join clause using the associations defined in the models and 
     * the structure of the used join array
     * 
     * @return string
     */
    protected function build_join_clause(): string
    {
        $join_statement = "";

        foreach ($this->joins as $key => $join) {
            // We have a nested model
            if (!is_numeric($key)) {
                // Join the parent model of the nested model first as the joined
                // table could be used in another join statement
                $join_statement .= $this->create_join_statement($this->base_model, $key);

                foreach ($join as $nested_join) {
                    $join_pk = $this->get_foreign_key($key, $nested_join);
                    // It might happen that no association was found between the
                    // nested base model and the nested model, in this case use
                    // the base model to perform the join
                    if (!isset($join_pk)) {
                        $join_statement .= $this->create_join_statement($this->base_model, $nested_join);
                    } else {
                        $join_statement .= $this->create_join_statement($key, $nested_join);
                    }
                }
            } else {
                $join_statement .= $this->create_join_statement($this->base_model, $join);
            }
        }

        return $join_statement;
    }

    /**
     * Get the foreign key of the association between the two given models
     * 
     * @param string $base_model    The model in which to search associations
     * @param string $join_model    The model to search in the base_model
     * 
     * @return string|null
     */
    private function get_foreign_key(string $base_model, string $join_model): ?string
    {
        if (array_key_exists($join_model, $base_model::$has_one)) {
            return $base_model::$has_one[$join_model];
        }

        if (array_key_exists($join_model, $base_model::$has_many)) {
            return $base_model::$has_many[$join_model];
        }

        return null;
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
