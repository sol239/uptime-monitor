<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\Models\Project;

class ProjectType extends GraphQLType
{
    protected $attributes = [
        'name' => 'Project',
        'description' => 'A project',
        'model' => Project::class,
    ];

    public function fields(): array
    {
        return [
            'identifier' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'Project identifier',
                'resolve' => function ($root) {
                    return $root->id;
                }
            ],
            'label' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Project label',
            ],
            'description' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Project description',
            ],
            'monitors' => [
                'type' => Type::listOf(Type::nonNull(GraphQL::type('Monitor'))),
                'description' => 'Project monitors',
                'resolve' => function ($root) {
                    return $root->monitors;
                }
            ],
        ];
    }
}
