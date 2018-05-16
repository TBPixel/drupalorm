<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Database\Queryable;


class PrimaryKeyIn implements Filterable
{
    /**
     * @var mixed
     */
    protected $ids;

    /**
     * @var string
     */
    protected $keyname;



    public function __construct($ids, string $keyname = 'id')
    {
        $this->ids     = $ids;
        $this->keyname = $keyname;
    }



    public function apply(Queryable $query) : void
    {
        $query->connection()->propertyCondition($this->keyname, $this->ids, 'IN');
    }
}
