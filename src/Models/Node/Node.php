<?php

namespace TBPixel\DrupalORM\Models\Node;

use TBPixel\DrupalORM\Models\Entity;
use DateTimeInterface;
use DateTime;
use TBPixel\DrupalORM\Models\Collection;


class Node extends Entity
{
    public static function entityType() : string
    {
        return 'node';
    }


    public static function primaryKey() : string
    {
        return 'nid';
    }


    public static function install(array $settings) : void
    {
        // Nothing to install if bundle is unset
        if (static::bundle() === null) return;


        $settings['type'] = static::bundle();

        $content_type = node_type_set_defaults($settings);

        node_type_save($content_type);


        foreach(static::fields()->bases() as $base)
        {
            field_create_field($base);
        }


        foreach(static::fields()->instances() as $instance)
        {
            $instance['entity_type'] = static::entityType();
            $instance['bundle']      = static::bundle();

            field_create_instance($instance);
        }
    }


    public static function uninstall() : void
    {
        // Nothing to uninstall if bundle is unset
        if (static::bundle() === null) return;


        // Chunk delete
        static::all()->chunk(200, function(Collection $nodes)
        {
            $node_ids = $nodes->map(
                function(Node $node) { return $node->id(); }
            );

            node_delete_multiple($node_ids);
        });


        // Delete field instances
        $fields = field_info_instances(static::entityType(), static::bundle());

        foreach ($fields as $instance)
        {
            field_delete_instance($instance);
        }


        node_type_delete(static::bundle());

        field_purge_batch(1000);
    }



    /**
     * Return a DateTimeInterface containing the date/time this node was created
     */
    public function created() : DateTimeInterface
    {
        return new DateTime("@{$this->created}");
    }


    /**
     * Return a DateTimeInterface containing the date/time this node was last changed
     */
    public function changed() : DateTimeInterface
    {
        return new DateTime("@{$this->changed}");
    }


    /**
     * Returns the relative url alias of this node
     */
    public function urlAlias() : string
    {
        return '/' . drupal_get_path_alias("node/{$this->nid}");
    }
}
