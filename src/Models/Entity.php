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
use stdClass;


abstract class Entity
{
    /**
     * Maintains all relationships retrieved into memory
     *
     * @var array
     */
    protected static $relationships = [];

    /**
     * @var Fields
     */
    protected static $fields;

    /**
     * Maintains a reference to the query class
     *
     * @var TBPixel\DrupalORM\Database\Queryable
     */
    protected $query;

    /**
     * Holds the entity instance
     *
     * @var object
     */
    protected $entity;



    public function __construct($entity = null)
    {
        $this->query = new DrupalQuery;
        $this->query->where(new TypeOf($this::entityType()));
        if ($this::bundle() !== null) $this->query->where(new GroupOf($this::bundle()));

        if ($entity !== null && is_object($entity)) $this->entity = $entity;
        else $this->entity = static::defaults(new stdClass);
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


            return (count($field) > 1) ? $field : field_view_value(static::entityType(), $this->entity, $name, $field[0]);
        }


        return $this->entity->{$name};
    }


    public function __set(string $name, $value) : void
    {
        $this->entity->{$name} = $value;
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
     * Sets the default values of the entity instance
     */
    abstract public static function defaults(stdClass $entity) : stdClass;


    /**
     * Executes saving of a new or existing Entity
     */
    abstract public function save() : Entity;


    /**
     * Executes deleting of a new or existing Entity
     */
    abstract public function delete() : Entity;


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
        if (static::bundle() === null) return new DrupalFields;


        if (static::$fields === null)
        {
            $bases          = field_info_fields();
            $instances      = field_info_instances(static::entityType(), static::bundle());
            $field_names    = array_keys($instances);

            $bases = array_filter(
                $bases,
                function(array $field, string $key) use ($field_names) { return in_array($key, $field_names); },
                ARRAY_FILTER_USE_BOTH
            );

            static::$fields = new DrupalFields($bases, $instances);
        }


        return static::$fields;
    }



    /**
     * Return a given resulting model or the default based on a given id
     */
    public static function find($ids) : Collection
    {
        $ids = is_array($ids) ? $ids : [$ids];

        $static = new static;

        $static->query->where(
            new PrimaryKeyIn($ids, $static::primaryKey())
        );

        $result = $static->get();


        return $static->get();
    }


    /**
     * Return a given resulting model or the default based on a given url
     */
    public static function findByUrl(string $url) : Collection
    {
        if (!is_array($path = path_load(['alias' => $url]))) return new Collection;

        $segments = explode('/', $path['source']);
        $id       = end($segments);


        return static::find($id);
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
        $result = $this->alter(
            new Limit(1)
        )->get()->first($default);


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
    protected function with(string $class, string $foreign_key, array $ids, string $column = null) : Entity
    {
        // Avoid an invalid class being passed in
        if (!class_exists($class) || !is_subclass_of($class, Entity::class)) throw new InvalidEntity("Class: {$class} is not a subclass of " . Entity::class);

        // Data in cache, exit early
        if (in_array($key = $this->relationshipKey($foreign_key), array_keys(static::$relationships))) return $this;


        $ids    = (new Collection($ids))->unique()->all();
        $column = $column ?? $class::primaryKey();


        if ($this->isField($foreign_key))
        {
            $subquery = db_select("field_data_{$foreign_key}", 'field');
            $subquery->fields('field', [
                "{$foreign_key}_" . $column
            ]);
            $subquery->condition('entity_type', static::entityType());
            $subquery->condition('entity_id', $this->id());

            static::$relationships[$key] = $class::all()
                ->where(
                    new PrimaryKeyIn(
                        $subquery,
                        $class::primaryKey()
                    )
                )
                ->get();
        }
        else
        {
            // Update in-memory relationships to reflect new get
            static::$relationships[$key] = $class::all()
                ->where(
                    new PrimaryKeyIn($ids, $foreign_key)
                )
                ->get();
        }


        return $this;
    }


    /**
     * Return the result of a One-To-One relationship
     */
    protected function hasOne(string $class, string $foreign_key, array $ids, string $column = null) : ?Entity
    {
        return $this->hasMany($class, $foreign_key, $ids, $column)->first();
    }


    /**
     * Return the result of a One-To-Many relationship
     */
    protected function hasMany(string $class, string $foreign_key, array $ids, string $column = null) : Collection
    {
        $key = $this->relationshipKey($foreign_key);

        $this->with($class, $foreign_key, $ids, $column);


        /** @var Collection $fetched */
        return static::$relationships[$key];
    }


    /**
     *
     */
    protected function relationshipKey(string $foreign_key) : string
    {
        return static::class . "::{$foreign_key}";
    }


    /**
     * Install and attach field instances to Entity
     */
    protected static function install_fields() : void
    {
        // Skip if Entity has no bundle
        if (static::bundle() === null) return;


        foreach(static::fields()->bases() as $base)
        {
            if (!field_info_field($base['field_name'])) field_create_field($base);
        }


        foreach(static::fields()->instances() as $instance)
        {
            if (!field_info_instance(static::entityType(), $instance['field_name'], static::bundle()))
            {
                $instance['entity_type'] = $instance['entity_type'] ?? static::entityType();
                $instance['bundle']      = $instance['bundle'] ?? static::bundle();

                field_create_instance($instance);
            }
        }
    }


    /**
     * Uninstall and detach instance fields from Entity
     */
    protected static function uninstall_fields() : void
    {
        // Skip if Entity has no bundle
        if (static::bundle() === null) return;


        foreach (static::fields()->instances() as $instance)
        {
            $entity_type = $instance['entity_type'] ?? static::entityType();
            $bundle      = $instance['bundle'] ?? static::bundle();

            if (field_info_instance($entity_type, $instance['field_name'], $bundle)) field_delete_instance($instance);
        }
    }



    /**
     * Returns if the given field name is a field of the current entity
     */
    protected function isField(string $name) : bool
    {
        return $this->fields()->find($name) !== null;
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
