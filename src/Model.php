<?php

namespace Expreql\Expreql;

use PDO;

abstract class Model implements Queryable
{

	/**
	 * @var PDO
	 * 
	 * The PDO instance
	 */
	public static $pdo;

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
	protected static $table;

	protected static function has_many()
	{
		return [];
	}

	protected static function has_one()
	{
		return [];
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
