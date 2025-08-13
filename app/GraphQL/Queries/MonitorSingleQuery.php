<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\Models\Monitor;

class MonitorSingleQuery extends Query
{
    protected $attributes = [
        'name' => 'monitor',
        'description' => 'Query a single monitor by identifier'
    ];

    public function type(): Type
    {
        return GraphQL::type('Monitor');
    }

    public function args(): array
    {
        return [
            'identifier' => [
                'type' => Type::nonNull(Type::id()),
                'description' => 'Monitor identifier',
            ],
        ];
    }

    public function resolve($root, array $args)
    {
        return Monitor::find($args['identifier']);
    }
}
