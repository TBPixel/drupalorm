<?php

namespace TBPixel\DrupalORM\Models;


interface Installable
{
    /**
     * Executes installation instructions for the implementation
     */
    public static function install(array $settings = []) : void;

    /**
     * Executes uninstallation instructions for the implementation
     */
    public static function uninstall() : void;
}
