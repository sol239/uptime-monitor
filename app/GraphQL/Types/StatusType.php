<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class StatusType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Status',
        'description' => 'Status of a monitor at a given time',
    ];

    public function fields(): array
    {
        return [
            'date' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'DateTime of the status (ISO8601, xsd:dateTime)',
                'resolve' => function ($root) {
                    return $root['date']->toIso8601String();
                },
            ],
            'ok' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'True if monitor succeeded',
                'resolve' => function ($root) {
                    return $root['ok'];
                },
            ],
            'responseTime' => [
                'type' => Type::int(),
                'description' => 'Response time in milliseconds',
            ],
        ];
    }
}
