<?php

require 'vendor/autoload.php';

use Expreql\Expreql\Database;

use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertNotNull;

class DatabaseTest extends TestCase
{
    public function testSetConfig()
    {
        $config = parse_ini_file("config.ini");

        Database::set_config($config);
        $connection = Database::get_connection();

        assertNotNull($connection);
    }
}