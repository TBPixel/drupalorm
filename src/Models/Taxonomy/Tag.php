<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Taxonomy\Taxonomy;


class Tag extends Taxonomy
{
    public static function bundle(): string
    {
        return 'tags';
    }
}
