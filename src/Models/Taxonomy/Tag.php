<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Taxonomy\Term;


class Tag extends Term
{
    public static function bundle(): string
    {
        return 'tags';
    }
}
