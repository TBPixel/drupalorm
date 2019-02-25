# Drupal Object Relational Mappings

NOTE: This project is unsupported and it is **highly** recommended that users reach for ORM implementations such as Doctrine or Eloquent.


## Installation

Require as a composer dependency with `composer require tbpixel/drupalorm:dev-master`


## How to use

```php
use TBPixel\DrupalORM\Models\Node\Node;

// Returns the first node in the database
$node = Node::all()->first();
// Or you can use object instantiation
$node = (new Node)->first();
```
