<?php

require 'vendor/autoload.php';

use Expreql\Expreql\Model;
use Expreql\Expreql\Database;

use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertIsInt;
use function PHPUnit\Framework\assertNotNull;

class Exercise extends Model
{
    public static $table = 'exercises';

    public static $primary_key = 'id';

    public static $fields = [
        'id',
        'title',
        'state',
    ];

    public static $has_many = [
        Question::class => 'exercises_id',
        Fulfillment::class => 'exercises_id',
    ];
}

class Question extends Model
{
    public static $table = 'questions';

    public static $primary_key = 'id';

    public static $fields = [
        'id',
        'label',
        'type',
        'exercises_id',
    ];

    public static $has_one = [
        Exercise::class
    ];
}

class Fulfillment extends Model
{
    public static $table = 'fulfillments';

    public static $primary_key = 'id';

    public static $fields = [
        'id',
        'timestamp',
    ];

    public static $has_one = [
        Exercise::class => 'exercises_id'
    ];
}

class Response extends Model
{
    public static $table = 'responses';

    public static $primary_key = 'id';

    public static $fields = [
        'id',
        'text',
        'questions_id',
        'fulfillments_id',
    ];

    public static $has_one = [
        Question::class => 'questions_id',
        Fulfillment::class => 'fulfillments_id',
    ];
}


class ModelTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = parse_ini_file("config.ini");

        Database::set_config($config);
        $connection = Database::get_connection();

        $schema_query = file_get_contents("./data/tests_schema.sql");
        $connection->query($schema_query);

        $data_query = file_get_contents("./data/tests_data.sql");
        $connection->query($data_query);

    }

    public function testSimpleSelect()
    {
        $exercises = Exercise::select()->execute();

        assertNotNull($exercises);
    }

    public function testGetFieldWithTableName()
    {
        assertEquals('exercises.title', Exercise::field('title'));
    }

    public function testJoinMany()
    {
        $exercise_with_question = Exercise::select()->join(Question::class)
            ->where('exercises.id', 1)->execute();

        assertNotNull($exercise_with_question);
    }

    public function testCountExercises()
    {
        $exercises = Exercise::select()->execute();

        assertIsInt($exercises->count());
    }

    public function testCountExerciseQuestions()
    {
        $exercise = Exercise::select()->where(Exercise::field('id'), 1)
            ->join(Question::class)->execute();

        assertIsInt($exercise[0]->questions->count());
    }

    public function testJoinWithNoJoinedRows()
    {
        $exercise = Exercise::select()->join(Question::class)
            ->where(Exercise::field('id'), 14)->execute();

        assertCount(0, $exercise[0]->questions);
    }

    public function testJoinMultipleModels()
    {
        $exercise = Exercise::select()->join([
            Fulfillment::class,
            Question::class,
        ])->where(Exercise::field('id'), 1)->execute();

        assertNotNull($exercise[0]->fulfillments);
        assertNotNull($exercise[0]->questions);
    }

    public function testFindExercise()
    {
        $exercise = Exercise::find(1)->execute();

        assertEquals($exercise->id, 1);
    }
}
