<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Entity;
use TBPixel\DrupalORM\Models\Taxonomy\Vocabulary;
use stdClass;


class Term extends Entity
{
    public static function entityType() : string
    {
        return 'taxonomy_term';
    }


    public static function primaryKey() : string
    {
        return 'tid';
    }


    public static function defaults(stdClass $entity) : stdClass
    {
        return $entity;
    }


    public function save() : Entity
    {
        return $this;
    }


    public function delete() : Entity
    {
        return $this;
    }



    public function vocabulary() : Vocabulary
    {
        return $this->hasOne(
            Vocabulary::class,
            'vid',
            [$this->vid]
        );
    }
}
