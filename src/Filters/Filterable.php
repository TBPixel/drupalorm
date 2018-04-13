<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Database\Queryable;


interface Filterable
{
    /**
     * Apply the filters alterations to the given query
     */
    public function apply(Queryable $query) : void;
}
