<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Taxonomy\Taxonomy;


class Tags extends Taxonomy
{
    public static function bundle(): string
    {
        return 'tags';
    }
}
