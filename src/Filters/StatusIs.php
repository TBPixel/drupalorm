<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Database\Queryable;


class StatusIs implements Filterable
{
    /**
     * @var string
     */
    protected $status;



    public function __construct(string $status)
    {
        $this->status = $status;
    }



    public function apply(Queryable $query) : void
    {
        $query->connection()->propertyCondition('status', $this->status);
    }
}
