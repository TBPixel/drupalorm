<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Database\Queryable;
use DateTimeInterface;


class UpdatedAt implements Filterable
{
    /**
     * @var DateTimeInterface
     */
    protected $datetime;

    /**
     * @var string
     */
    protected $operator;



    public function __construct(DateTimeInterface $datetime, string $operator = '=')
    {
        $this->datetime = $datetime;
        $this->operator = $operator;
    }



    public function apply(Queryable $query) : void
    {
        $query->connection()->propertyCondition('changed', $this->datetime->getTimestamp(), $this->operator);
    }
}
