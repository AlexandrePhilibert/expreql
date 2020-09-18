<?php

require 'query_builder.php';

abstract class Model
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

	/**
	 * @param array $config Contains the config for PDO
	 *    $config = [
	 *      'host' => (string),
	 *      'db' => (string),
	 *      'charset' => (string),
	 *      'user' => (string),
	 *      'pass' => (string)
	 *    ]
	 */
	public static function set_config(array $config)
	{
		$host = $config['host'];
		$db = $config['db'];
		$charset = $config['charset'];
		$user = $config['user'];
		$pass = $config['pass'];
		$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_CLASS,
			PDO::ATTR_EMULATE_PREPARES   => true,
		];

		self::$pdo = new PDO($dsn, $user, $pass, $options);
	}

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
