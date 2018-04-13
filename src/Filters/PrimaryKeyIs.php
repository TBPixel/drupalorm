<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Database\Queryable;


class PrimaryKeyIs implements Filterable
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $keyname;



    public function __construct(string $id, string $keyname = 'id')
    {
        $this->id      = $id;
        $this->keyname = $keyname;
    }



    public function apply(Queryable $query) : void
    {
        $query->connection()->propertyCondition($this->keyname, $this->id);
    }
}
