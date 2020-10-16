<?php

namespace Expreql\Expreql;

use ArrayObject;

class QueryResult extends ArrayObject
{
    public function __construct(array $array = [])
    {
        return parent::__construct($array);
    }
}
