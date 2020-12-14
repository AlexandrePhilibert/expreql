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

    public static $has_many = [
        // foreign key mapping the Question model to `exercises_id`
        Question::class => 'exercises_id'
    ];
    
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

// "INSERT INTO products (`name`, `price`, `storage`) VALUES (?, ?, ?)"
// Expreql uses prepared statements to prevent against SQL injections
```

### Select query

The returned values are converted using `htmlspecialchars` to prevent XSS injections, you can use `htmlspecialchars_decode` to decode a selected string. Note that this can lead to XSS injections.

```php
$books = Book::select([
    'isbn',
    'title',
    'published_year'
])->where('published_year', 2018)->execute();

// "SELECT `isbn`, `title`, `published_year` FROM `books` WHERE `published_year` = 2018"
```

### Update query

The Update query returns the number of affetcted rows

```php
$nbRowsUpdated = Car::update([
    'leased' => 1, // 1 equals true
])->where([
    ['licence_plate', 'VD9043209'],
])->execute();

// "UPDATE `cars` SET `leased`=? WHERE `licence_plate`=?"
```

### Delete query

```php
$nbRowsDeleted = Article::delete()->where([
    ['status', 'out_of_stock'],
    ['quantity', 0],
])->execute();

// "DELETE FROM `articles` WHERE `status`=? AND `quantity`=?"
```
