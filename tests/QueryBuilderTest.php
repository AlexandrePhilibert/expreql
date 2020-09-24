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
            'SELECT furnitures.name, furnitures.price, furnitures.dimensions FROM furnitures',
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
            'SELECT furnitures.name, furnitures.price, furnitures.dimensions FROM furnitures WHERE id = ? AND price <= ?',
            $query_builder->statement->queryString
        );
    }
}
