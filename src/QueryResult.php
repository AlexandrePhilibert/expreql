<?php

namespace Expreql\Expreql;

use ArrayObject;

class QueryResult extends ArrayObject
{

    public function __construct(?array $input = []) 
    {
        parent::__construct($input, ArrayObject::STD_PROP_LIST);
    }
}
