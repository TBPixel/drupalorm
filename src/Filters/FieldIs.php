<?php

namespace TBPixel\DrupalORM\Filters;

use TBPixel\DrupalORM\Database\Queryable;
use TBPixel\DrupalORM\Filters\Filterable;


class FieldIs implements Filterable
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var string
     */
    protected $operator;



    public function __construct(string $name, string $column, $value, string $operator = '=')
    {
        $this->name     = $name;
        $this->column   = $column;
        $this->value    = $value;
        $this->operator = $operator;
    }



    public function apply(Queryable $query) : void
    {
        $query->connection()->fieldCondition(
            $this->name,
            $this->column,
            $this->value,
            $this->operator
        );
    }
}