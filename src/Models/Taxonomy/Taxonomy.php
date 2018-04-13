<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Entity;


class Taxonomy extends Entity
{
    /**
     * @var string
     */
    protected $entity_type = 'taxonomy_term';

    /**
     * @var string
     */
    protected $primary_key = 'tid';



    /**
     * Returns the relative url alias of this taxonomy
     */
    public function urlAlias() : string
    {
        return drupal_get_path_alias("taxonomy/term/{$this->tid}");
    }
}
