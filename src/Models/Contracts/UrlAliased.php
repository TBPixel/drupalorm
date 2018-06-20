<?php

namespace TBPixel\DrupalORM\Models\Contracts;


interface UrlAliased
{
    /**
     * Returns the url alias of the Model
     */
    public function urlAlias() : string;
}
