<?php

namespace TBPixel\DrupalORM\Models;

use TBPixel\DrupalORM\Models\Collection;
use TBPixel\DrupalORM\Database\DrupalQuery;
use TBPixel\DrupalORM\Filters\{
    Filterable,
    TypeOf,
    GroupOf,
    PrimaryKeyIs
};
use TBPixel\DrupalORM\Alterations\Alterable;


abstract class Entity
{
    /**
     * Defines the entity_type of the content to query
     *
     * @var string
     */
    protected $entity_type;

    /**
     * Defines the bundle of the content to query
     *
     * @var string
     */
    protected $bundle;

    /**
     * Defines the primary key of the content to query
     *
     * @var string
     */
    protected $primary_key;

    /**
     * Maintains a reference to the query class
     *
     * @var TBPixel\DrupalORM\Database\Queryable
     */
    protected $query;

    /**
     * Holds the entity instance
     *
     * @var mixed
     */
    protected $entity;



    public function __construct($entity = null)
    {
        $this->query = new DrupalQuery;
        $this->query->where(new TypeOf($this->entity_type));
        if ($this->bundle !== null) $this->query->where(new GroupOf($this->bundle));

        if ($entity !== null && is_object($entity)) $this->entity = $entity;
    }


    /**
     * Force deep-clone of nested objects
     */
    public function __clone()
    {
        $this->query  = clone $this->query;
        $this->entity = ($this->entity !== null) ? clone $this->entity : null;
    }


    /**
     * Defers all field gets to the entity instance
     */
    public function __get(string $name)
    {
        if (property_exists($this->entity, $name)) return $this->entity->{$name};
    }


    /**
     * Defers all field isset and empty checks to entity instance
     */
    public function __isset(string $name) : bool
    {
        return (
            property_exists($this->entity, $name) &&
            isset($this->entity->{$name})
        );
    }



    /**
     * Return a given resulting model or the default based on a given id
     */
    public static function find(int $id, $default = null) : ?Entity
    {
        $static = new static;

        $static->query->where(
            new PrimaryKeyIs($id, $static->primary_key)
        );


        return $static->first($default);
    }


    /**
     * Return an instance of Entity ready to get all results
     */
    public static function all() : Entity
    {
        return new static;
    }


    /**
     * Applies filters to the instances query
     */
    public function where(Filterable ...$filters) : Entity
    {
        $this->query->where(...$filters);


        return $this;
    }


    /**
     * Applies alterations to the instances query
     */
    public function alter(Alterable ...$alterations) : Entity
    {
        $this->query->alter(...$alterations);


        return $this;
    }


    /**
     * Retrieve the first result from a set of results
     */
    public function first($default = null) : ?Entity
    {
        $result = $this->get()->first($default);


        return $result ?? $default;
    }


    /**
     * Return a collection of the results
     */
    public function get() : Collection
    {
        $data   = $this->query->execute();
        $result = $this->load(
            $this->getIds($data['result'])
        );


        return new Collection(
            $this->mapEntitiesAsModels($result)
        );
    }


    /**
     * Iterate all results by chunking the data to reduce memory overhead
     */
    public function chunk(int $limit, callable $callback) : void
    {
        $count     = (clone $this)->count();
        $chunksize = round($count / $limit);

        for ($i = 0; $i < $chunksize; $i++)
        {
            $offset  = $limit * $i;
            $results = (clone $this)->limit($limit, $offset)->get();

            $callback($results);
        }
    }


    /**
     * Return a count of the queries results
     */
    public function count() : int
    {
        $data = $this->query->count()->execute();


        return $data['result'];
    }


    /**
     * Limit the size of the result set withing a given range
     */
    public function limit(int $limit, int $offset = 0) : Entity
    {
        $this->query->range($offset, $limit);


        return $this;
    }



    /**
     * Map entities to models
     */
    protected function mapEntitiesAsModels(array $entities) : array
    {
        return array_map(
            function($entity) { return new static($entity); },
            $entities
        );
    }


    /**
     * Load the entities by an array of their ids
     */
    protected function load(array $ids) : array
    {
        return !empty($ids) ? entity_load($this->entity_type, $ids) : [];
    }


    /**
     * Retrieve the ids for the entities given the query result set
     */
    protected function getIds(array $result) : array
    {
        return isset($result[$this->entity_type]) ? array_keys($result[$this->entity_type]) : [];
    }
}
