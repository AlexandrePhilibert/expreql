<?php

namespace Expreql\Expreql;

use ArrayObject;

class QueryResult extends ArrayObject
{

    private $model;

    public function __construct(array $fetch_result = [], string $model = null, array $join_model = null)
    {

        if (empty($fetch_result)) {
            return;
        }

        $this->model = $model;

        // Create all base models
        foreach ($fetch_result as $row) {

            // We can skip this row if we already have a base instance
            foreach ($this as $model_instance) {
                $primary_key = $model::$primary_key;
                if (
                    is_array($row[$primary_key]) &&
                    in_array($model_instance->$primary_key, $row[$primary_key])
                ) {
                    continue 2;
                }
                if ($model_instance->$primary_key == $row[$primary_key]) {
                    continue 2;
                }
            }

            $flatten_row = array_map(function ($column) {
                if (is_array($column)) {
                    return $column[0];
                }
                return $column;
            }, $row);

            $this->append(new $model($flatten_row));
        }

        if (!isset($join_model)) {
            // We can return what we got as there are no join to perform
            return $this;
        }

        foreach ($join_model as $join_index => $join) {
            // Get the foregin key to map the base model primary key to
            // the join model foreign key
            $foreign_key = $model::$has_many[$join];
            $primary_key = $model::$primary_key;
            $join_table_name = $join::$table;

            // Iterate a second time, this time creating joined models
            foreach ($fetch_result as $row) {
                if (!isset($row[$foreign_key])) {
                    // We have not joined any rows, create empty QueryResult
                    // property on each object in order to not have null value
                    foreach ($this as $model_instance) {
                        if (is_array($row[$primary_key])) {
                            $row_primary_key = $row[$primary_key][0];
                        } else {
                            $row_primary_key = $row[$primary_key];
                        }
                        if ($model_instance->$primary_key == $row_primary_key) {
                            $join_query_result = new QueryResult();
                            $model_instance->$join_table_name = $join_query_result;
                            break;
                        }
                    }
                    continue;
                }
                // We want to set the base model as a property of the base model
                if (is_array($join)) {

                } else {
                // Find the base model to which we will be adding joined object
                $base_model = $this->get_base_model($row, $join);
                }


                // flatten the row to remove
                $flatten_row = array_map(function ($column) use ($join_index) {
                    if (is_array($column)) {
                        return $column[$join_index];
                    }
                    return $column;
                }, $row);

                $join_model = new $join($flatten_row);

                if (is_array($join)) {
                } else {
                    // Joined models should also be a QueryResult in order to
                    // perform methods on them
                    if (isset($base_model->$join_table_name)) {
                        $base_model->$join_table_name->append($join_model);
                    } else {
                        $join_query_result = new QueryResult();
                        $join_query_result->append($join_model);
                        $base_model->$join_table_name = $join_query_result;
                    }
                }
            }
        }
    }

    private function get_base_model($row, $join): Model
    {
        $foreign_key = $this->model::$has_many[$join];
        $primary_key = $this->model::$primary_key;

        foreach ($this as $model_instance) {
            if (is_array($row[$foreign_key])) {
                // TODO: Index could be greater than 1 (collision between 3 cols...)
                if ($model_instance->$primary_key == $row[$foreign_key][1]) {
                    return $model_instance;
                }
            }
            if ($model_instance->$primary_key == $row[$foreign_key]) {
                return $model_instance;
            }
        }
    }
}
