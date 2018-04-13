<?php

namespace TBPixel\DrupalORM\Alterations;

use TBPixel\DrupalORM\Database\Queryable;


interface Alterable
{
    /**
     * Apply alterations to the given query
     */
    public function apply(Queryable $query) : void;
}
