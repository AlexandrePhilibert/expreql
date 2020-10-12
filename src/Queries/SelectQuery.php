<?php

namespace Expreql\Expreql;

use PDO;
use PDOStatement;

class SelectQuery extends Query
{

    /**
     * The fields being selected 
     * 
     * @var array
     */
    public? array $fields;

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

        $query .= " FROM $table";

        if (isset($this->joins)) {
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

    public function execute(): QueryResult
    {
        $statement = $this->build();

        if (isset($this->values)) {
            $statement->execute($this->values);
        } else {
            $statement->execute();
        }

        $fetch_result = $statement->fetchAll(PDO::FETCH_NAMED);

        return new QueryResult($fetch_result, $this->base_model, $this->joins);
    }
}
