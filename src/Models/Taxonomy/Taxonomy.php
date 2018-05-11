<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Entity;


abstract class Taxonomy extends Entity
{
    public static function entityType() : string
    {
        return 'taxonomy_term';
    }


    public static function primaryKey() : string
    {
        return 'tid';
    }



    /**
     * Returns the relative url alias of this taxonomy
     */
    public function urlAlias() : string
    {
        return '/' . drupal_get_path_alias("taxonomy/term/{$this->tid}");
    }
}
