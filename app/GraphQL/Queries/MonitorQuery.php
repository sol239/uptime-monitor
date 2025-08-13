<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\Models\Monitor;

class MonitorQuery extends Query
{
    protected $attributes = [
        'name' => 'monitors',
        'description' => 'Query monitors'
    ];

    public function type(): Type
    {
        return Type::listOf(Type::nonNull(GraphQL::type('Monitor')));
    }

    public function resolve($root, array $args)
    {
        return Monitor::all();
    }
}
