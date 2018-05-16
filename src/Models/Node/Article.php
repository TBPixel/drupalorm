<?php

namespace TBPixel\DrupalORM\Models\Node;

use TBPixel\DrupalORM\Models\Collection;
use TBPixel\DrupalORM\Models\Node\Node;
use TBPixel\DrupalORM\Models\Taxonomy\Tag;
use TBPixel\DrupalORM\Fields\{
    Fields,
    DrupalFields
};
use stdClass;


class Article extends Node
{
    public static function bundle() : ?string
    {
        return 'article';
    }


    public static function defaults(stdClass $entity) : stdClass
    {
        $entity = parent::defaults($entity);

        $entity->title = '';


        return $entity;
    }


    public function tags() : Collection
    {
        return $this->hasMany(
            Tag::class,
            'field_tags',
            [$this->id()]
        );
    }
}
