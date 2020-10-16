<?php

namespace Expreql\Expreql;

use PDO;
use PDOStatement;

/**
 * This class extracts the building of the QueryResult structure out of the 
 * QueryResult class to have a vanilla object for the end-user to use without 
 * any hidden methods and properties.
 */
class ResultBuilder
{
    /**
     * Contains all the created models stored by model classes
     * 
     * @var array
     */
    private array $models = [];

    /**
     * @param PDOStatement $statement  The executed PDO statement
     * @param array $model_classes  An array of model classes that could contain
     *   nested models
     * 
     * @return QueryResult
     */
    public function get_query_result(PDOStatement $statement, array $model_classes): QueryResult
    {
        $flat_model_classes = $this->flatten_model_classes($model_classes);

        $this->initialize_model_list($flat_model_classes);

        while ($row = $statement->fetch(PDO::FETCH_NAMED)) {
            $this->create_row_models($row, $flat_model_classes, $model_classes);
        }

        return new QueryResult($this->models[$flat_model_classes[0]]);
    }

    /**
     * @param array $array  Nested arrays of models classes
     * 
     * @return array  Flat array of model classes
     */
    private function flatten_model_classes(array $array): array
    {
        $new_array = [];
        foreach ($array as $key => $value) {
            // Nested array models have a model class name as the key, push it
            // into the flat array aswell
            if (!is_numeric($key)) {
                array_push($new_array, $key);
            }
    
            if (is_array($value)) {
                array_push($new_array, ...$this->flatten_model_classes($value));
            } else {
                array_push($new_array, $value);
            }
        }
    
        return $new_array;
    }

    private function initialize_model_list(array $flat_model_classes)
    {
        foreach ($flat_model_classes as $model_classes) {
            $this->models[$model_classes] = [];
        }
    }

    /**
     * Create all the models possible from a single SQL row
     * 
     * @param array $row
     * @param array $model_classes
     * 
     * @return array
     */
    private function create_row_models(array $row, array $flat_model_classes, array $model_classes)
    {
        $row_models = [];

        foreach ($flat_model_classes as $model_class) {
            $model = new $model_class();
            $primary_key = $model_class::$primary_key;

            foreach ($row as $column_key => $column_value) {
                // Skip this column as it does not belong to this model
                if (!in_array($column_key, $model_class::$fields)) {
                    continue;
                }

                if (is_array($column_value)) {
                    // NOTE: Here we only need the maped value of the 
                    // corresponding model_class and not the others 
                    $maped_values = $this->map_model_class_to_value($column_key, $column_value, $flat_model_classes);
                    $model->$column_key = $maped_values[$model_class];
                } else {
                    $model->$column_key = $column_value;
                }
            }

            // We do not append the model if does already exist or if no primary
            // key was found, as this means the left join did not join anything.
            // NOTE: We are doing unnecessary work in the processing of the row
            // as we are creating models that are then thrown away, but the PDO 
            // fetch return structure forces our hand...
            $existing_model = $this->get_model($model->$primary_key, $model_class);

            if (isset($model->$primary_key)) {
                if (!isset($existing_model)) {
                    $this->models[$model_class][] = $model;
                    $row_models[] = $model;
                } else {
                    // Append the exisiting model to the row models result as
                    // we need all the models to perform the merge
                    $row_models[] = $existing_model;
                }
            }
        }

        $this->traverse_model_classes($row_models, key($model_classes), $model_classes);
    }

    /**
     * Recursively walk the arrays of models
     * 
     * @param mixed $row_models  The models created in a single SQL response row
     *   This array does not contain any duplicates if they were already created 
     *   in another row
     * @param mixed $base_model_class
     * @param mixed $model_classes  The join models
     * 
     * TODO: Refactor this into a new ModelWalker class 
     */
    private function traverse_model_classes($row_models, $base_model_class, $model_classes)
    {
        foreach ($model_classes as $key => $model_class) {
            // If model_class is an array we continue the tree traversal
            if (is_array($model_class)) {
                if (is_numeric($key)) {
                    // NOTE: Does this work when we have multiple joins (not nested) ?
                    // as we are returning an not continuing the foreach loop ?
                    $this->traverse_model_classes($row_models, $base_model_class, $model_class);
                } else {
                    // We do not want to append the base model inside the base model...
                    if ($key != $base_model_class) {
                        $this->append_model($row_models, $base_model_class, $key);                       
                    }

                    $this->traverse_model_classes($row_models, $key, $model_classes[$key]);
                }
            } else {
                // We arrived at a node without any child model classes, append 
                // the join model to the base model found in the row_models list
                $this->append_model($row_models, $base_model_class, $model_class);
            }
        }
    }

    /**
     * @param Model[] $row_models
     * @param string $base_model_class
     * @param string $join_model_class
     * 
     */
    private function append_model(array $row_models, string $base_model_class, string $join_model_class)
    {
        $join_table_name = $join_model_class::$table;

        foreach ($row_models as $model) {
            if ($model instanceof $base_model_class) {
                $base_model = $model;
                break;
            }
        }

        foreach ($row_models as $model) {
            if ($model instanceof $join_model_class) {
                $join_model = $model;
                break;
            }
        }

        // The base model could be non-existant if the models created
        // from the SQL row did not join anything, this means a nested 
        // join will try to join on an non-existant base model.
        if (isset($base_model)) {
            if (!isset($base_model->$join_table_name)) {
                $base_model->$join_table_name = new QueryResult();
            }

            if (isset($join_model) && !$this->model_has_child($base_model, $join_model)) {
                $base_model->$join_table_name->append($join_model);
            }
        }
    }

    private function model_has_child(Model $base_model, Model $join_model): bool
    {
        $join_table_name = $join_model::$table;
        $join_pk = $join_model::$primary_key;

        foreach ($base_model->$join_table_name as $model) {
            if ($model->$join_pk == $join_model->$join_pk) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int|string $value  primary key value of existing model
     * @param string  the model class of the model instance we are searching
     * 
     * @return Model|null
     */
    private function get_model($value, string $model_class): ?Model
    {
        foreach ($this->models[$model_class] as $model) {
            $primary_key = $model::$primary_key;
            if ($model->$primary_key == $value) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Map the values to the corresponding model_classes for the given column
     * 
     * @param string $column
     * @param array $values
     * @param array $model_classes
     * 
     * @return array
     */
    private function map_model_class_to_value(string $column, array $values, array $model_classes): array
    {
        $result = [];
        $index = 0;

        foreach ($model_classes as $model) {
            if (in_array($column, $model::$fields)) {
                $result[$model] = $values[$index];
                $index++;
            }
        }

        return $result;
    }
}
