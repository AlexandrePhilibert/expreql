<?php

namespace Expreql\Expreql;

use PDO;
use PDOStatement;

class UpdateQuery extends Query
{
    /**
     * The fields being selected 
     * 
     * @var array
     */
    public ?array $fields;

    public function build(): PDOStatement
    {
        $table = $this->get_base_table_name();
        $query = "UPDATE $table SET";

        foreach ($this->fields as $key => $value) {
            $this->values[] = $value;
            $query .=  " $key = ?,";
        }

        $query = substr($query, 0, strlen($query) - 1);

        if (isset($this->where)) {
            $query .= $this->build_where_clause();
        }

        return $this->connection->prepare($query);
    }

    /**
     * @return int  The number of rows affected by the UpdateQuery
     */
    public function execute(): int
    {
        $statement = $this->build();

        if (isset($this->values)) {
            $statement->execute($this->values);
        } else {
            $statement->execute();
        }

        return $statement->rowCount();
    }
}
