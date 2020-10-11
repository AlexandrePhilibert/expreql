<?php

namespace Expreql\Expreql;

use PDOStatement;

class DeleteQuery extends Query
{
    public function build(): PDOStatement
    {
        $table = $this->get_base_table_name();
        $query = "DELETE FROM $table";

        if (isset($this->where)) {
            $query .= $this->build_where_clause();
        }

        return $this->pdo->prepare($query);
    }

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
