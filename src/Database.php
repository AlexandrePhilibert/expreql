<?php

namespace Expreql\Expreql;

use PDO;

/**
 * Singleton holding the PDO connection to a database
 */
class Database
{

    private static $user;

    private static $pass;

    private static $dsn;

    /**
     * @var PDO
     */
    private static $connection;

    private function __construct()
    {
    }

    /**
     * Either get or create the PDO connection to the database
     * 
     * @return PDO
     */
    public static function get_connection()
    {
        if (isset(self::$connection)) {
            return self::$connection;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_CLASS,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ];

        self::$connection = new PDO(self::$dsn, self::$user, self::$pass, $options);

        return self::$connection;
    }

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

        self::$user = $config['user'];
        self::$pass = $config['pass'];
        self::$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    }
}
