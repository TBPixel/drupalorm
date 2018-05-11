<?php

namespace TBPixel\DrupalORM\Models\Taxonomy;

use TBPixel\DrupalORM\Models\Entity;


class Taxonomy extends Entity
{
    public static function entityType() : string
    {
        return 'taxonomy_term';
    }


    public static function primaryKey() : string
    {
        return 'tid';
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



    /**
     * Returns the relative url alias of this taxonomy
     */
    public function urlAlias() : string
    {
        return '/' . drupal_get_path_alias("taxonomy/term/{$this->tid}");
    }
}
