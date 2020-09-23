<?php

require 'vendor/autoload.php';

use Expreql\Expreql\QueryBuilder;
use Expreql\Expreql\QueryType;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{

    private $query_builder;

    protected function setUp(): void
    {
        $config = parse_ini_file("config.ini");
        $dsn = "mysql:host=" . $config['host'] . ";dbname=" . $config['db'] .
            ";charset=" . $config['charset'];

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_CLASS,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ];
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        $this->query_builder = new QueryBuilder(QueryType::INSERT, $pdo);
    }

    public function testBasicInsert()
    {
        $this->query_builder->table('books');
        $this->query_builder->fields([
            'title' => "Harry Potter and the Philosopher's Stone",
            'isbn' => '0-7475-3269-9',
        ]);
        $this->query_builder->build();

        $this->assertEquals(
            'INSERT INTO `books` (`title`, `isbn`) VALUES (?,?)',
            $this->query_builder->statement->queryString
        );
    }
}
