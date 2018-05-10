<?php

namespace TBPixel\DrupalORM\Fields;

use TBPixel\DrupalORM\Fields\Fields;


class DrupalFields implements Fields
{
    /**
     * @var array
     */
    protected $bases;

    /**
     * @var array
     */
    protected $instances;



    public function __construct(array $bases = [], array $instances = [])
    {
        $this->bases     = $bases;
        $this->instances = $instances;
    }



    public function bases() : array
    {
        return $this->bases;
    }


    public function instances() : array
    {
        return $this->instances;
    }
}
