<?php

namespace TBPixel\DrupalORM\Database;

use TBPixel\DrupalORM\Database\Queryable;
use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Alterations\Alterable;
use EntityFieldQuery;


class DrupalQuery implements Queryable
{
    /**
     * @var EntityFieldQuery
     */
    protected $query;



    public function __construct()
    {
        $this->query = new EntityFieldQuery;
    }


    public function __clone()
    {
        $this->query = clone $this->query;
    }



    public function execute() : array
    {
        return [
            'result' => $this->query->execute()
        ];
    }


    public function count() : Queryable
    {
        $this->query->count();


        return $this;
    }


    public function where(Filterable ...$filters) : Queryable
    {
        foreach ($filters as $filter)
        {
            $filter->apply($this);
        }


        return $this;
    }


    public function alter(Alterable ...$alterations) : Queryable
    {
        foreach ($alterations as $alteration)
        {
            $alteration->apply($this);
        }


        return $this;
    }


    public function connection()
    {
        return $this->query;
    }
}
