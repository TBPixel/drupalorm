<?php

namespace TBPixel\DrupalORM\Models\Node;

use TBPixel\DrupalORM\Models;


class Article extends Models\Node\Node
{
    public static function bundle() : ?string
    {
        return 'article';
    }
}
