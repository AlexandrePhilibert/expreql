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
	 * @var string
	 * 
	 * The name of the database table which the model represents
	 */
	protected static $table;

	private function __construct()
	{
	}

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
		$query_builder = new QueryBuilder(QueryType::INSERT, static::$pdo);
		$query_builder->table(static::$table);
		$query_builder->fields($fields);
		return $query_builder->execute();
	}

	public static function select(array $fields = null)
	{
		$query_builder = new QueryBuilder(QueryType::SELECT, static::$pdo);
		$query_builder->table(static::$table);
		$query_builder->fields($fields);
		$query_builder->has_many(static::has_many());
		$query_builder->has_one(static::has_one());
		return $query_builder;
	}

	public static function update(array $fields)
	{
		$query_builder = new QueryBuilder(QueryType::UPDATE, static::$pdo);
		$query_builder->table(static::$table);
		$query_builder->fields($fields);
		return $query_builder;
	}

	public static function delete()
	{
		$query_builder = new QueryBuilder(QueryType::DELETE, static::$pdo);
		$query_builder->table(static::$table);
		return $query_builder;
	}
}
