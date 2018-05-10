<?php

namespace TBPixel\DrupalORM\Models\Node;

use TBPixel\DrupalORM\Models;
use TBPixel\DrupalORM\Fields\{
    Fields,
    DrupalFields
};


class Article extends Models\Node\Node
{
    public static function bundle() : ?string
    {
        return 'article';
    }


    public static function fields() : Fields
    {
        return new DrupalFields(
            // Bases
            [
                'body' => [
                    'field_name'   => 'body',
                    'type'         => 'text_with_summary',
                    'entity_types' => [static::entityType()]
                ]
            ],
            // Instances
            [
                'body' => [
                    'field_name' => 'body',
                    'entity_type' => 'node',
                    'bundle' => static::bundle(),
                    'label' => 'Body',
                    'widget' => [
                        'type' => 'text_textarea_with_summary'
                    ],
                    'settings' => [
                        'display_summary' => true
                    ],
                    'display' => [
                        'default' => [
                            'label' => 'hidden',
                            'type'  => 'text_default'
                        ],
                        'teaser' => [
                            'label' => 'hidden',
                            'type'  => 'text_summary_or_trimmed'
                        ]
                    ]
                ]
            ]
        );
    }


    public function categories() : Models\Collection
    {
        return $this->hasMany(
            Models\Taxonomy\Taxonomy::class,
            'field_blog_category'
        );
    }
}
