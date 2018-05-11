<?php

namespace TBPixel\DrupalORM\Models\Node;

use TBPixel\DrupalORM\Models;
use TBPixel\DrupalORM\Fields\{
    Fields,
    DrupalFields
};


class Article extends Models\Node\Node
{
    public static function bundle() : ?string
    {
        return 'article';
    }


    public function categories() : Models\Collection
    {
        return $this->hasMany(
            Models\Taxonomy\Taxonomy::class,
            'field_blog_category'
        );
    }
}
