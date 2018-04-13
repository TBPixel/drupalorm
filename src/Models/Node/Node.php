<?php

namespace TBPixel\DrupalORM\Models\Node;

use TBPixel\DrupalORM\Models\Entity;
use DateTimeInterface;
use DateTime;


class Node extends Entity
{
    /**
     * @var string
     */
    protected $entity_type = 'node';

    /**
     * @var string
     */
    protected $primary_key = 'nid';



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
