<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Database\Queryable;


class GroupOf implements Filterable
{
    /**
     * @var string
     */
    protected $group;



    public function __construct(string $group)
    {
        $this->group = $group;
    }



    public function apply(Queryable $query) : void
    {
        $query->connection()->entityCondition('bundle', $this->group);
    }
}
