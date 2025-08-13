<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;

class QueryType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Query',
        'description' => 'A type'
    ];

    public function fields(): array
    {
        return [
            'projects' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Project')))),
                'description' => 'Get all projects',
            ],
            'status' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Status')))),
                'description' => 'Get status for a monitor',
                'args' => [
                    'monitorIdentifier' => [
                        'type' => Type::nonNull(Type::string()),
                        'description' => 'Monitor identifier',
                    ],
                    'from' => [
                        'type' => Type::int(),
                        'description' => 'From timestamp',
                    ],
                    'to' => [
                        'type' => Type::int(),
                        'description' => 'To timestamp',
                    ],
                ],
            ],
            'monitors' => [
                'type' => Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Monitor')))),
                'description' => 'Query monitors',
            ],
        ];
    }
}
