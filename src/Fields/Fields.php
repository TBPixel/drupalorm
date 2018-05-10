<?php

namespace TBPixel\DrupalORM\Fields;


interface Fields
{
    /**
     * Return a Drupal 7 compatible array of field bases
     */
    public function bases() : array;

    /**
     * Return a Drupal 7 compatible array of field instances
     */
    public function instances() : array;
}
