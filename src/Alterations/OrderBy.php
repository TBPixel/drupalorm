<?php

namespace TBPixel\DrupalORM\Alterations;

use TBPixel\DrupalORM\Alterations\Alterable;
use TBPixel\DrupalORM\Database\Queryable;


class OrderBy implements Alterable
{
    /**
     * @var string
     */
    protected $column;

    /**
     * @var string
     */
    protected $direction;



    public function __construct(string $column, string $direction = 'ASC')
    {
        $this->column    = $column;
        $this->direction = $direction;
    }


    public function apply(Queryable $query) : void
    {
        $query->connection()->propertyOrderBy($this->column, $this->direction);
    }
}