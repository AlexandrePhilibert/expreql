<?php

namespace Expreql\Expreql;

/**
 * Define DQL sub-language actions.
 */
interface Queryable
{

    /**
     * @param array|null $fields
     */
    public static function select(array $fields = null);

    /**
     * @param mixed $id    Find a single record using the primary key
     * 
     */
    public static function find($value);

    /**
     * @param array $fields
     */
    public static function insert(array $fields);

    /**
     * @param array $fields
     */
    public static function update(array $fields);

    public static function delete();
}
