<?php

namespace TBPixel\DrupalORM\Models\User;

use TBPixel\DrupalORM\Models\Entity;
use TBPixel\DrupalORM\Exceptions\InvalidEntity;


class User extends Entity
{
    public static function primaryKey(): string
    {
        return 'uid';
    }

    public static function entityType(): string
    {
        return 'user';
    }

    public static function defaults($entity)
    {
        $entity->{static::primaryKey()} = null;
        $entity->name                   = '';
        $entity->pass                   = '';
        $entity->mail                   = '';
        $entity->theme                  = '';
        $entity->signature              = '';
        $entity->signature_format       = null;
        $entity->created                = time();
        $entity->access                 = '0';
        $entity->login                  = '0';
        $entity->timezone               = null;
        $entity->language               = '';
        $entity->picture                = null;
        $entity->init                   = '';
        $entity->data                   = false;
        $entity->roles                  = ['anonymous user'];


        return $entity;
    }


    public function save() : Entity
    {
        user_save($this->entity);


        return $this;
    }


    public function delete() : Entity
    {
        if ($this->id() === null) throw new InvalidEntity("User must have an ID to be deleted.");

        user_delete($this->id());


        return $this;
    }
}
