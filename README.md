# expreql

Expreql is a query builder and ORM that wraps around PDO. It allows to write queries without worrying about SQL.

:warning: Expreql is not production-ready, use it at your own risks :D

## Usage

### Model definition

By defining a model you gain access to the different functions to interact with the SQL table that corresponds to the `$table` variable.

```php
class Exercise extends Model
{
    // Name of the SQL table
    public static $table = 'exercises';
 
    public static $primary_key = 'id';

      protected static function has_many() {
        return [
            // foreign key mapping the Question model to `exercises_id`
            Question::class => 'exercises_id'
        ];
    }
}
```

### Insert query

```php
// Note that we do not need to call `execute` on insert queries
Product::insert([
    'name' => 'Apple Iphone 10X',
    'price' => 1100.10,
    'storage' => 256
]);

// "INSERT INTO products (`name`, `price`, `storage`) VALUES ('apple IPhone 10X', 1100.10, 256)"
// Expreql uses prepared statements to prevent against SQL injections
```

### Select query

```php
$books = Book::select([
    'isbn',
    'title',
    'published_year'
])->where('published_year', 2018)->execute();
```
