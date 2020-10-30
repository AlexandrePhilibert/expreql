<?php

namespace Expreql\Expreql;

use PDOStatement;

class SelectQuery extends Query
{

    /**
     * The fields being selected 
     * 
     * @var array|null
     */
    public ?array $fields;

    /**
     * @var int
     */
    public int $limit;

    /**
     * @var int
     */
    public int $offset;

    /**
     * @var array
     */
    public array $order_by;

    public function build(): PDOStatement
    {
        $table = $this->get_base_table_name();

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
                    $query .= " $field,";
                }
            }

            // Remove trailing comma
            $query = substr($query, 0, strlen($query) - 1);
        } else {
            $query = "SELECT *";
        }

        $query .= " FROM `$table`";

        if (count($this->joins) > 0) {
            $query .= $this->build_join_clause();
        }

        if (isset($this->where)) {
            $query .= $this->build_where_clause();
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

        return $this->connection->prepare($query);
    }

    public function execute()
    {
        $statement = $this->build();

        if (isset($this->values)) {
            $statement->execute($this->values);
        } else {
            $statement->execute();
        }

        $result_builder = new ResultBuilder();

        if (count($this->joins) == 0) {
            $model_classes = [
                $this->base_model => []
            ];
        }

        // Build the structure of the model classes used for this query.
        // We cannot unpack the $this->joins array as it contains string keys.
        foreach ($this->joins as $join_key => $join_array) {
            $model_classes[$this->base_model][$join_key] = $join_array;
        }

        return $result_builder->get_query_result($statement, $model_classes);
    }
}
