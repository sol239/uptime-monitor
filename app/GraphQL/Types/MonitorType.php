<?php

namespace App\GraphQL\Types;

use App\Models\Monitor;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class MonitorType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Monitor',
        'description' => 'A monitor',
        'model' => Monitor::class,
    ];

    public function fields(): array
    {
        return [
            'identifier' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'Monitor identifier',
                'resolve' => function ($root) {
                    return $root->id;
                },
            ],
            'periodicity' => [
                'type' => Type::int(),
                'description' => 'Monitor periodicity',
            ],
            'label' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Monitor label',
            ],
            'type' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Monitor type',
                'resolve' => function ($root) {
                    return $root->monitor_type;
                },
            ],
            'host' => [
                'type' => Type::string(),
                'description' => 'Monitor host',
                'resolve' => function ($root) {
                    return $root->hostname;
                },
            ],
            'url' => [
                'type' => Type::string(),
                'description' => 'Monitor URL',
            ],

            'badgeUrl' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Monitor badge URL',
                'resolve' => function ($root) {
                    $appUrl = env('APP_URL', 'http://localhost:8000');

                    return "{$appUrl}/api/v1/badge/{$root->id}";
                },
            ],
        ];
    }
}
