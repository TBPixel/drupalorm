<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Entity;
use TBPixel\DrupalORM\Models\Collection;
use TBPixel\DrupalORM\Models\Installable;
use TBPixel\DrupalORM\Exceptions\InvalidEntity;
use stdClass;


class Vocabulary extends Entity implements Installable
{
    public static function entityType() : string
    {
        return 'taxonomy_vocabulary';
    }


    public static function primaryKey() : string
    {
        return 'vid';
    }


    public static function install(array $settings = []) : void
    {
        // Nothing to install if bundle is unset
        if (static::bundle() === null) return;


        $settings['name']         = $settings['name'] ?? static::bundle();
        $settings['description']  = $settings['description'] ?? '';
        $settings['machine_name'] = static::bundle();


        taxonomy_vocabulary_save((object) $settings);

        static::install_fields();
    }


    public static function uninstall() : void
    {
        // Nothing to install if bundle is unset
        if (static::bundle() === null) return;

        $vocab = taxonomy_vocabulary_machine_name_load(static::bundle());

        // Nothing to uninstall if vocabulary does not exist
        if (!$vocab) return;

        static::uninstall_fields();

        taxonomy_vocabulary_delete($vocab->vid);
    }



    public static function defaults(stdClass $entity) : stdClass
    {
        $entity->name         = '';
        $entity->machine_name = '';
        $entity->description  = '';
        $entity->module       = 'taxonomy';
        $entity->hierarchy    = 0;
        $entity->weight       = 0;


        return $entity;
    }


    public function save() : Entity
    {
        if (!isset($this->entity->machine_name) || $this->entity->machine_name === null) throw new InvalidEntity('Taxonomy Vocabulary must have a machine_name to be saved!');

        taxonomy_vocabulary_save($this->entity);


        return $this;
    }


    public function delete() : Entity
    {
        if (!isset($this->entity->{static::primaryKey()}) || $this->entity->{static::primaryKey()} === null) throw new InvalidEntity('Taxonomy Vocabulary must have an entity instance to be deleted!');

        taxonomy_vocabulary_delete($this->entity->{static::primaryKey()});


        return $this;
    }


    public function terms() : Collection
    {
        return $this->hasMany(
            Term::class,
            static::primaryKey(),
            static::primaryKey()
        );
    }


    /**
     * Returns the relative url alias of this taxonomy
     */
    public function urlAlias() : string
    {
        return '/' . drupal_get_path_alias("taxonomy/term/{$this->tid}");
    }
}
