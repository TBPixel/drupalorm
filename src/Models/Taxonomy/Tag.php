<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Taxonomy\Term;
use TBPixel\DrupalORM\Models\Node\Article;
use TBPixel\DrupalORM\Models\Collection;


class Tag extends Term
{
    public static function bundle() : string
    {
        return 'tags';
    }


    public function articles() : Collection
    {
        return $this->belongsTo(
            Article::class,
            static::primaryKey(),
            'field_tags'
        );
    }
}
