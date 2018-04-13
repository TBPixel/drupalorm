<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Database\Queryable;


class TypeOf implements Filterable
{
    /**
     * @var string
     */
    protected $type;



    public function __construct(string $type)
    {
        $this->type = $type;
    }



    public function apply(Queryable $query) : void
    {
        $query->connection()->entityCondition('entity_type', $this->type);
    }
}
