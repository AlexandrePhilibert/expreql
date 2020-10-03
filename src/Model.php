<?php

namespace Expreql\Expreql;

abstract class Model implements Queryable
{

	/**
	 * Register the table columns name
	 * 
	 * TODO(alexandre): Should we use `DESCRIBE` instead of asking the user to 
	 *
	 * @var array
	 */
	public static $fields = [];

	/**
	 * @var string
	 * 
	 * The name of the database table which the model represents
	 */
	public static $table;

	public static function has_many()
	{
		return [];
	}

	public static function has_one()
	{
		return [];
	}

	/**
	 * This function can be used to avoid name collisions on tables columns.
	 * 
	 * @param string $field The field of the model colliding
	 * 
	 * @return string
	 */
	public static function field(string $field): string
	{
		return static::$table . "." . $field;
	}

	public static function insert(array $fields)
	{
		$connection = Database::get_connection();
		$query_builder = new QueryBuilder(QueryType::INSERT, $connection);
		$query_builder->table(static::$table);
		$query_builder->fields($fields);
		return $query_builder->execute();
	}

	public static function select(array $fields = null)
	{
		$connection = Database::get_connection();
		$query_builder = new QueryBuilder(QueryType::SELECT, $connection);
		$query_builder->model(static::class);
		$query_builder->table(static::$table);
		$query_builder->fields($fields);
		$query_builder->has_many(static::has_many());
		$query_builder->has_one(static::has_one());
		return $query_builder;
	}

	public static function update(array $fields)
	{
		$connection = Database::get_connection();
		$query_builder = new QueryBuilder(QueryType::UPDATE, $connection);
		$query_builder->table(static::$table);
		$query_builder->fields($fields);
		return $query_builder;
	}

	public static function delete()
	{
		$connection = Database::get_connection();
		$query_builder = new QueryBuilder(QueryType::DELETE, $connection);
		$query_builder->table(static::$table);
		return $query_builder;
	}
}
