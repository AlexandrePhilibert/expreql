<?php

require 'vendor/autoload.php';

use Expreql\Expreql\InsertQuery;
use Expreql\Expreql\QueryBuilder;
use Expreql\Expreql\SelectQuery;
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
        $query_builder = new QueryBuilder(InsertQuery::class, $this->pdo);
        $query_builder->table('books');
        $query_builder->fields([
            'title' => "Harry Potter and the Philosopher's Stone",
            'isbn' => '0-7475-3269-9',
        ]);
        $statement = $query_builder->build();

        $this->assertEquals(
            'INSERT INTO `books` (`title`, `isbn`) VALUES (?,?)',
            $statement->queryString
        );
    }

    public function testBasicSelect()
    {
        $query_builder = new QueryBuilder(SelectQuery::class, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->fields([
            'name',
            'price',
            'dimensions',
        ]);
        $statement = $query_builder->build();

        $this->assertEquals(
            'SELECT name, price, dimensions FROM furnitures',
            $statement->queryString
        );
    }

    public function testSelectMultipleWhere()
    {
        $query_builder = new QueryBuilder(SelectQuery::class, $this->pdo);
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
        $statement = $query_builder->build();

        $this->assertEquals(
            'SELECT name, price, dimensions FROM furnitures WHERE id = ? AND price <= ?',
            $statement->queryString
        );
    }

    public function testSelectLimit()
    {
        $query_builder = new QueryBuilder(SelectQuery::class, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->limit(100);
        $statement = $query_builder->build();

        $this->assertEquals(
            'SELECT * FROM furnitures LIMIT 100',
            $statement->queryString
        );
    }

    public function testSelectOffset()
    {
        $query_builder = new QueryBuilder(SelectQuery::class, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->offset(100);
        $statement = $query_builder->build();

        $this->assertEquals(
            'SELECT * FROM furnitures OFFSET 100',
            $statement->queryString
        );
    }

    public function testMultipleWhere()
    {
        $query_builder = new QueryBuilder(SelectQuery::class, $this->pdo);
        $query_builder->table('furnitures');
        $query_builder->where('price', '<=', 50);
        $query_builder->where_or([
            ['availability', 'in_stock'],
            ['availability', 'ordered'],
        ]);
        $statement = $query_builder->build();

        $this->assertEquals(
            "SELECT * FROM furnitures WHERE price <= ? AND (availability = ? OR availability = ?)",
            $statement->queryString
        );
    }

    public function testJoin()
    {
        $query_builder = new QueryBuilder(SelectQuery::class, $this->pdo);
        $query_builder->base_model(Exercise::class);
        $query_builder->join([Question::class]);
        $query_builder->where(Exercise::field('id'), 8);
        $statement = $query_builder->build();

        $this->assertEquals(
            "SELECT * FROM exercises " .
                "LEFT JOIN questions ON exercises.id = questions.exercises_id " .
                "WHERE exercises.id = ?",
            $statement->queryString
        );
    }

    public function testNestedJoins()
    {
        $query_builder = new QueryBuilder(SelectQuery::class, $this->pdo);
        $query_builder->base_model(Exercise::class);
        $query_builder->join([
            Question::class,
            Fulfillment::class => [
                Response::class,
            ]
        ]);
        $query_builder->where(Exercise::field('id'), 8);
        $statement = $query_builder->build();

        $this->assertEquals(
            "SELECT * FROM exercises " .
                "LEFT JOIN questions ON exercises.id = questions.exercises_id " .
                "LEFT JOIN fulfillments ON exercises.id = fulfillments.exercises_id " .
                "LEFT JOIN responses ON fulfillments.id = responses.fulfillments_id " .
                "WHERE exercises.id = ?",
            $statement->queryString
        );
    }
}
