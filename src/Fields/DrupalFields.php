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


    public function find(string $name) : ?array
    {
        $base     = $this->filter($this->bases, $name);
        $instance = $this->filter($this->instances, $name);

        if (empty($base) || empty($instance)) return null;


        return [
            'base'     => $base,
            'instance' => $instance
        ];
    }


    protected function filter(array $fields, string $name) : array
    {
        return array_filter(
            $fields,
            function(array $field) use ($name) { return $field['field_name'] === $name; }
        );
    }
}
