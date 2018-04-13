<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Database\Queryable;


class TitleIs implements Filterable
{
    /**
     * @var string
     */
    protected $title;



    public function __construct(string $title)
    {
        $this->title = $title;
    }



    public function apply(Queryable $query) : void
    {
        $query->connection()->propertyCondition('title', $this->title);
    }
}
