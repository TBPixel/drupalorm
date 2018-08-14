<?php
/**
 * TODO
 *  - Cache query results to prevent duplicate requests, especially when performing relationship queries
 */

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
     * Maintain a cache of query results
     *
     * @var array
     */
    protected static $cache = [];

    /**
     * Maintain an associative array of class field Fields
     *
     * @var array
     */
    protected static $fields = [];

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
        else $this->entity = static::defaults($entity);
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

        if (static::isField($name)) return field_get_items($this::entityType(), $this->entity, $name);


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
    abstract public static function defaults($entity);


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


        if (!isset(static::$fields[static::class]) || static::$fields[static::class] === null)
        {
            $bases          = field_info_fields();
            $instances      = field_info_instances(static::entityType(), static::bundle());
            $field_names    = array_keys($instances);

            $bases = array_filter(
                $bases,
                function(array $field, string $key) use ($field_names) { return in_array($key, $field_names); },
                ARRAY_FILTER_USE_BOTH
            );

            static::$fields[static::class] = new DrupalFields($bases, $instances);
        }


        return static::$fields[static::class];
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
        $data = $this->query->execute();

        $data = $this->load(
            $this->getIds($data['result'])
        );


        return new Collection(
            $this->mapEntitiesAsModels($data)
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
    public function id() : ?int
    {
        return $this->entity->{$this::primaryKey()} ?? null;
    }


    /**
     * Return the type of the Entity
     */
    public function type() : string
    {
        return $this::entityType();
    }


    /**
     * Render a field value by name
     */
    public function render(string $name, int $delta = 0) : ?string
    {
        if (!$this->isField($name)) return null;

        $field = $this->{$name};
        $value = field_view_value(
            $this->entityType(),
            $this->entity,
            $name,
            $field[$delta]
        );


        return $value['#access'] ? drupal_render($value) : null;
    }


    /**
     * Executes a relationship query given all the required data, optionally accepting a unique filter with a field foreign key fallback
     */
    protected function with(string $class, array $id_set, string $foreign_key, string $foreign_join_key, Filterable $filter) : Collection
    {
        // Avoid an invalid class being passed in
        if (!class_exists($class) || !is_subclass_of($class, Entity::class)) throw new InvalidEntity("Class: {$class} is not a subclass of " . Entity::class);

        // Load the cached relationship if available
        if (in_array($key = $this->relationshipKey($class, $foreign_key), static::$cache)) return static::$cache[$key];


        static::$cache[$key] = $class::all()->where($filter)->get();


        return static::$cache[$key];
    }


    /**
     * Defines the relationship of Model that has one child
     */
    protected function hasOne(string $class, string $foreign_key, string $foreign_join_key) : ?Entity
    {
        /* Models resulting from the current query. */
        $models   = $this->get();
        $child_id = (new Collection([$this->{$foreign_key}]))->flatten()->first();
        $id_set   = $models
            ->map(
                function(Entity $entity) { return $entity->id(); }
            )
            ->unique()
            ->all();

        if (!static::isField($foreign_key)) { $filter = new PrimaryKeyIn($id_set, $foreign_join_key); }
        else
        {
            $subquery = db_select("field_data_{$foreign_key}", 'field');
            $subquery->fields('field', [
                "{$foreign_key}_" . $foreign_join_key
            ]);
            $subquery->condition('entity_type', static::entityType());
            $subquery->condition('entity_id', $id_set, 'IN');


            $filter = new PrimaryKeyIn(
                $subquery,
                $class::primaryKey()
            );
        }


        return $this->with($class, $id_set, $foreign_key, $foreign_join_key, $filter)
            ->filter(
                function(Entity $entity) use ($child_id) { return $entity->id() == $child_id; }
            )
            ->first();
    }


    /**
     * Defines the relationship of a Model that has many children
     */
    protected function hasMany(string $class, string $foreign_key, string $foreign_join_key) : Collection
    {
        /* Models resulting from the current query. */
        $models       = $this->get();
        $child_id_set = (new Collection([$this->{$foreign_key}]))->flatten()->all();
        $id_set       = $models
            ->map(
                function(Entity $entity) { return $entity->id(); }
            )
            ->unique()
            ->all();


        if (!static::isField($foreign_key)) { $filter = new PrimaryKeyIn($id_set, $foreign_join_key); }
        else
        {
            $subquery = db_select("field_data_{$foreign_key}", 'field');
            $subquery->fields('field', [
                "{$foreign_key}_" . $foreign_join_key
            ]);
            $subquery->condition('entity_type', static::entityType());
            $subquery->condition('entity_id', $id_set, 'IN');


            $filter = new PrimaryKeyIn(
                $subquery,
                $class::primaryKey()
            );
        }


        return $this
            ->with($class, $id_set, $foreign_key, $foreign_join_key, $filter)
            ->filter(
                function(Entity $entity) use ($foreign_join_key, $child_id_set) { return in_array($entity->{$foreign_join_key}, $child_id_set); }
            );
    }


    /**
     * Defines the relationship of a Model that belongs to a parent
     */
    protected function belongsTo(string $class, string $foreign_key, string $foreign_join_key) : Collection
    {
        /* Models resulting from the current query. */
        $models = $this->get();
        $id_set = $models
            ->map(
                function(Entity $entity) use ($foreign_key) { return $entity->{$foreign_key}; }
            )
            ->unique()
            ->all();


        if (!$class::isField($foreign_join_key)) { $filter = new PrimaryKeyIn($id_set, $foreign_key); }
        else
        {
            $subquery = db_select("field_data_{$foreign_join_key}", 'field');
            $subquery->fields('field', ['entity_id']);
            $subquery->condition('entity_type', $class::entityType());
            $subquery->condition("{$foreign_join_key}_{$foreign_key}", $id_set, 'IN');


            $filter = new PrimaryKeyIn(
                $subquery,
                $class::primaryKey()
            );
        }


        return $this
            ->with($class, $id_set, $foreign_key, $foreign_join_key, $filter);
    }


    /**
     * Returns a key representing a unique relationship result
     */
    protected function relationshipKey(string $class, string $foreign_key) : string
    {
        return static::class . "::{$class}" . "->{$foreign_key}";
    }


    /**
     * Install and attach field instances to Entity
     */
    protected static function install_fields() : void
    {
        // Skip if Entity has no bundle
        if (static::bundle() === null) return;


        foreach (static::fields()->bases() as $base)
        {
            if (!field_info_field($base['field_name'])) field_create_field($base);
        }


        foreach (static::fields()->instances() as $instance)
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
     * Deletes field instances from the database
     */
    protected static function delete_fields() : void
    {
        // Skip if Entity has no bundle
        if (static::bundle() === null) return;


        foreach (static::fields()->bases() as $base)
        {
            $field = $base['field_name'];

            if (field_info_field($field)) field_delete_field($field);
        }
    }



    /**
     * Returns if the given field name is a field of the current entity
     */
    protected static function isField(string $name) : bool
    {
        return static::fields()->find($name) !== null;
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
