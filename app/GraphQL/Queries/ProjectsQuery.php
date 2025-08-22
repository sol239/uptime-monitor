<?php

namespace App\GraphQL\Queries;

use App\Models\Project;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

class ProjectsQuery extends Query
{
    protected $attributes = [
        'name' => 'projects',
        'description' => 'Get all projects',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::listOf(Type::nonNull(GraphQL::type('Project'))));
    }

    public function args(): array
    {
        return [];
    }

    public function resolve($root, $args)
    {
        return Project::all();
    }
}
