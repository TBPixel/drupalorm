<?php

namespace TBPixel\DrupalORM\Models;

use TBPixel\DrupalORM\Models\Collection;
use TBPixel\DrupalORM\Database\DrupalQuery;
use TBPixel\DrupalORM\Alterations\Alterable;
use TBPixel\DrupalORM\Fields\{
    Fields,
    DrupalFields
};
use TBPixel\DrupalORM\Filters\{
    Filterable,
    TypeOf,
    GroupOf,
    PrimaryKeyIn
};
use TBPixel\DrupalORM\Alterations\{
    Limit
};
use TBPixel\DrupalORM\Exceptions\InvalidEntity;


abstract class Entity
{
    /**
     * Maintains all relationships retrieved into memory
     *
     * @var array
     */
    protected static $relationships = [];

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
        $this->query->where(new TypeOf($this::entityType()));
        if ($this::bundle() !== null) $this->query->where(new GroupOf($this::bundle()));

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
        if (!property_exists($this->entity, $name)) return;


        if ($this->isField($name))
        {
            $field = field_get_items($this::entityType(), $this->entity, $name);


            return $field;
        }


        return $this->entity->{$name};
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
     * Returns the primary key of the entity
     */
    abstract public static function primaryKey() : string;


    /**
     * Returns the type of the entity
     */
    abstract public static function entityType() : string;


    /**
     * Returns the bundle of the entity, if set
     */
    public static function bundle() : ?string
    {
        return null;
    }


    /**
     * Returns a Fields instance for reading defined field types of a Model
     */
    public static function fields() : Fields
    {
        return new DrupalFields;
    }



    /**
     * Return a given resulting model or the default based on a given id
     */
    public static function find($ids, $default = null) : ?Entity
    {
        $ids = is_array($ids) ? $ids : [$ids];

        $static = new static;

        $static->query->where(
            new PrimaryKeyIn($ids, $static::primaryKey())
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
        $chunksize = ceil($count / $limit);

        for ($i = 0; $i < $chunksize; $i++)
        {
            $offset  = $limit * $i;
            $results = (clone $this)->alter(
                new Limit($limit, $offset)
            )->get();

            $callback($results);
        }
    }


    /**
     * Return a count of the queries results
     */
    public function count() : int
    {
        $data = (clone $this)->query->count()->execute();


        return $data['result'];
    }


    /**
     * Return the ID of the Entity
     */
    public function id() : int
    {
        return $this->entity->{$this::primaryKey()};
    }


    /**
     * Return the type of the Entity
     */
    public function type() : string
    {
        return $this::entityType();
    }


    /**
     * Executes a relationship on a given model
     */
    protected function with(string $Class, string $foreign_key, string $primary_key = null) : Entity
    {
        // Avoid an invalid class being passed in
        if (!class_exists($Class) || !is_subclass_of($Class, Entity::class)) throw new InvalidEntity("Class: {$Class} is not a subclass of " . Entity::class);

        // Data in cache, exit early
        if (in_array($foreign_key, array_keys(static::$relationships))) return $this;


        $primary_key = $primary_key ?? $Class::primaryKey();
        $foreign_ids = new Collection;

        // Retrieve all foreing ids as a single collection
        foreach ($this->get() as $entity)
        {
            if (!is_array($entity->{$foreign_key})) continue;

            $foreign_ids = $foreign_ids->merge(
                array_map(
                    function(array $item) use ($primary_key) { return $item[$primary_key]; },
                    $entity->{$foreign_key}
                )
            );
        }

        // Filter out all but unique ids and return as an array
        $foreign_ids = $foreign_ids->unique()->all();

        // Update in-memory relationships to reflect new get
        static::$relationships[$foreign_key] = $Class::all()
            ->where(
                new PrimaryKeyIn($foreign_ids, $Class::primaryKey())
            )
            ->get();


        return $this;
    }


    /**
     * Return the result of a One-To-One relationship
     */
    protected function hasOne(string $Class, string $foreign_key, string $primary_key = null) : ?Entity
    {
        return $this->hasMany($Class, $foreign_key, $primary_key)->first();
    }


    /**
     * Return the result of a One-To-Many relationship
     */
    protected function hasMany(string $Class, string $foreign_key, string $primary_key = null) : Collection
    {
        if (!is_array($this->{$foreign_key})) return new Collection;
        if (!in_array($foreign_key, array_keys(static::$relationships))) $this->with($Class, $foreign_key, $primary_key);

        $primary_key  = $primary_key ?? $Class::primaryKey();
        $relationship = new Collection;

        /** @var Collection $fetched */
        $fetched = static::$relationships[$foreign_key];

        $ids = array_map(
            function(array $item) use ($primary_key) { return $item[$primary_key]; },
            $this->{$foreign_key}
        );

        foreach ($ids as $id)
        {
            $relationship = $relationship->merge(
                $fetched->filter(function(Entity $entity) use ($id) { return $id == $entity->id(); })
            );
        }


        return $relationship;
    }



    /**
     * Returns if the given field name is a field of the current entity
     */
    protected function isField(string $name) : bool
    {
        $field_names = array_keys(
            field_language($this::entityType(), $this->entity)
        );


        return (
            $this->entity !== null &&
            in_array($name, $field_names)
        );
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
        return !empty($ids) ? entity_load($this::entityType(), $ids) : [];
    }


    /**
     * Retrieve the ids for the entities given the query result set
     */
    protected function getIds(array $result) : array
    {
        return isset($result[$this::entityType()]) ? array_keys($result[$this::entityType()]) : [];
    }
}
