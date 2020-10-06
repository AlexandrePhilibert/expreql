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
        Question::class => 'exercises_id'
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


class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        $config = parse_ini_file("config.ini");

        Database::set_config($config);
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

    public function testFindExercise()
    {
        $exercise = Exercise::find(1)->execute();

        assertEquals($exercise->id, 1);
    }
}
