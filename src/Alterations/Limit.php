<?php

namespace TBPixel\DrupalORM\Alterations;

use TBPixel\DrupalORM\Alterations\Alterable;
use TBPixel\DrupalORM\Database\Queryable;


class Limit implements Alterable
{
    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;



    public function __construct(int $limit, int $offset = 0)
    {
        $this->limit  = $limit;
        $this->offset = $offset;
    }


    public function apply(Queryable $query) : void
    {
        $query->connection()->range($this->offset, $this->limit);
    }
}