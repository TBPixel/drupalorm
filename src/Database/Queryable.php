<?php

namespace TBPixel\DrupalORM\Database;

use TBPixel\DrupalORM\Filters\Filterable;
use TBPixel\DrupalORM\Alterations\Alterable;


interface Queryable
{
    /**
     * Executes the query, returning an associative array of data keyed into the 'result' index
     */
    public function execute() : array;

    /**
     * Sets the query to be a count query, returning an instance of self
     */
    public function count() : Queryable;

    /**
     * Applies filters to the query, returning an instance of self
     */
    public function where(Filterable ...$filters) : Queryable;

    /**
     * Applies alterations to the query, returning an instance of self
     */
    public function alter(Alterable ...$alterations) : Queryable;

    /**
     * Return the connection implementation
     */
    public function connection();
}
