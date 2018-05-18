<?php

namespace TBPixel\DrupalORM\Models;

use ArrayIterator;
use Traversable;

use Countable;
use IteratorAggregate;
use JsonSerializable;


class Collection implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @var array
     */
    protected $items;



    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }


    public function __toString() : string
    {
        return $this->jsonSerialize();
    }



    /**
     * Return the array of collection results
     */
    public function all() : array
    {
        return $this->items;
    }


    /**
     * Return if the collection is empty or not
     */
    public function isEmpty() : bool
    {
        return empty($this->items);
    }


    /**
     * Return the first item in the collection
     */
    public function first($default = null)
    {
        return ($result = reset($this->items)) ? $result : $default;
    }


    /**
     * Return the last item in the collection
     */
    public function last($default = null)
    {
        return end($this->items) ?? $default;
    }


    /**
     * Return a new collection containing the keys of the current collection
     */
    public function keys() : self
    {
        return new static(
            array_keys($this->items)
        );
    }


    /**
     * Return a new collection containing the results of a filter by key
     */
    public function find($key) : self
    {
        return $this->filter(
            function($v, $k) use ($key) { return $key == $k; }
        );
    }


    /**
     * Return a new collection containing a reversed order of the current collections items
     */
    public function reverse() : self
    {
        return new static(
            array_reverse($this->items)
        );
    }


    /**
     * Returns a new collection containing the items sorted
     */
    public function sort(callable $sorting_func) : self
    {
        return new static(
            usort($this->items, $sorting_func)
        );
    }


    /**
     * Return a filtered collection of the current items with the given callback
     */
    public function filter(callable $callback = null) : self
    {
        if ($callback)
        {
            return new static(
                array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH)
            );
        }


        return new static(
            array_filter($this->items)
        );
    }


    /**
     * Return a collection after executing a unique sort
     */
    public function unique(int $sort_flags = SORT_STRING) : self
    {
        return new static(
            array_unique($this->items, $sort_flags)
        );
    }


    /**
     * Return a mapped collection of the current items with the given callback
     */
    public function map(callable $callback) : self
    {
        $keys  = $this->keys()->all();
        $items = array_map($callback, $this->items, $keys);


        return new static(
            array_combine($keys, $items)
        );
    }


    /**
     * Return a collection containing the merging of two iterable instances
     */
    public function merge($items) : self
    {
        return new static(
            array_merge($this->items, $this->getArrayableItems($items))
        );
    }


    /**
     * Return a collection that contains the flattened results of a multi-dimensional array
     */
    public function flatten() : self
    {
        $result = [];

        foreach ($this->items as $item)
        {
            if ($item instanceof static) $item = $item->all();


            if (!is_array($item)) $result[] = $item;
            else
            {
                $result = array_merge(
                    $result,
                    (new static($item))->flatten()->all()
                );
            }
        }


        return new static($result);
    }


    /**
     * Countable implementation
     *
     * Return a count of the collection size
     */
    public function count() : int
    {
        return count($this->items);
    }


    /**
     * IteratorAggregate implementation
     *
     * Return an ArrayIterator of the collections items
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->items);
    }


    /**
     * JsonSerializable implementation
     *
     * Return a json encoded string of the collections items
     */
    public function jsonSerialize() : string
    {
        return json_encode($this->items);
    }



    /**
     * Return an array based on if the given entry is an array or a collection
     */
    protected function getArrayableItems($items) : array
    {
        if ($items instanceof Collection)
        {
            return array_values(
                $items->all()
            );
        }


        return array_values($items);
    }
}
