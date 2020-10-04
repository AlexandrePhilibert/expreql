<?php

require 'vendor/autoload.php';

use Expreql\Expreql\QueryBuilder;
use Expreql\Expreql\QueryType;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{

    private $pdo;

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
        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    }

    public function testBasicInsert()
    {
        $query_builder = new QueryBuilder(QueryType::INSERT, $this->pdo);
        $query_builder->table('books');
        $query_builder->fields([
            'title' => "Harry Potter and the Philosopher's Stone",
            'isbn' => '0-7475-3269-9',
        ]);
        $query_builder->build();

        $this->assertEquals(
            'INSERT INTO `books` (`title`, `isbn`) VALUES (?,?)',
            $query_builder->statement->queryString
        );
    }

    public function testBasicSelect() {
        $query_builder = new QueryBuilder(QueryType::SELECT, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->fields([
            'name',
            'price',
            'dimensions',
        ]);
        $query_builder->build();

        $this->assertEquals(
            'SELECT name, price, dimensions FROM furnitures',
            $query_builder->statement->queryString
        );
    }

    public function testSelectMultipleWhere() {
        $query_builder = new QueryBuilder(QueryType::SELECT, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->fields([
            'name',
            'price',
            'dimensions',
        ]);
        $query_builder->where([
            ['id', 12],
            ['price', '<=', 50]
        ]);
        $query_builder->build();

        $this->assertEquals(
            'SELECT name, price, dimensions FROM furnitures WHERE id = ? AND price <= ?',
            $query_builder->statement->queryString
        );
    }

    public function testSelectLimit() {
        $query_builder = new QueryBuilder(QueryType::SELECT, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->limit(100);
        $query_builder->build();

        $this->assertEquals(
            'SELECT * FROM furnitures LIMIT 100',
            $query_builder->statement->queryString
        );
    }

    public function testSelectOffset() {
        $query_builder = new QueryBuilder(QueryType::SELECT, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->offset(100);
        $query_builder->build();

        $this->assertEquals(
            'SELECT * FROM furnitures OFFSET 100',
            $query_builder->statement->queryString
        );
    }

    public function testMultipleWhere() {
        $query_builder = new QueryBuilder(QueryType::SELECT, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->where('price', '<=', 50);
        $query_builder->where_or([
            ['availability', 'in_stock'],
            ['availability', 'ordered'],
        ]);
        $query_builder->build();

        $this->assertEquals(
            "SELECT * FROM furnitures WHERE price <= ? AND (availability = ? OR availability = ?)",
            $query_builder->statement->queryString
        );
    }
}
