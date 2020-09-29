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
    public static function select(?array $fields);

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
