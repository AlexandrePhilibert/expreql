<?php

require 'vendor/autoload.php';

use Expreql\Expreql\Model;
use Expreql\Expreql\Database;

use PHPUnit\Framework\TestCase;

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

    protected static function has_many()
    {
        return [
            Question::class => 'exercises_id'
        ];
    }
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

    protected static function has_one()
    {
        return [
            Exercise::class
        ];
    }
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

    public function testJoinMany()
    {
        $exercise_with_question = Exercise::select()->join(Question::class)
            ->where('exercises.id', 1)->execute();

        print_r($exercise_with_question);

        assertNotNull($exercise_with_question);
    }
}