<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Entity;
use TBPixel\DrupalORM\Models\Collection;
use TBPixel\DrupalORM\Models\Taxonomy\Vocabulary;
use TBPixel\DrupalORM\Exceptions\InvalidEntity;


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


    public static function defaults($entity)
    {
        $entity->{static::primaryKey()}  = null;
        $entity->vid                     = null;
        $entity->name                    = '';
        $entity->description             = '';
        $entity->format                  = 'plain_text';
        $entity->weight                  = 0;
        $entity->vocabulary_machine_name = static::bundle();


        return $entity;
    }


    public function save() : Entity
    {
        if (!isset($this->entity->vid) || $this->entity->vid === null) throw new InvalidEntity("Taxonomy Term must have an associated vocabulary ID to be saved!");
        if (static::bundle() === null) throw new InvalidEntity('Taxonomy Term must have a bundle to be saved!');

        taxonomy_term_save($this->entity);


        return $this;
    }


    public function delete() : Entity
    {
        if ($this->id() === null) throw new InvalidEntity("Taxonomy Term must have an ID set to be deleted!");

        taxonomy_term_delete($this->id());


        return $this;
    }



    public function vocabulary() : Collection
    {
        return $this->belongsTo(
            Vocabulary::class,
            Vocabulary::primaryKey(),
            Vocabulary::primaryKey()
        );
    }
}
