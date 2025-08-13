<?php

namespace App\GraphQL\Queries;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Query;
use Rebing\GraphQL\Support\Facades\GraphQL;
use App\Models\Monitor;

class StatusQuery extends Query
{
    protected $attributes = [
        'name' => 'status',
        'description' => 'Get monitor statuses in a given period',
    ];

    public function type(): Type
    {
        return Type::listOf(GraphQL::type('Status'));
    }

    public function args(): array
    {
        return [
            'monitorIdentifier' => [
                'type' => Type::nonNull(Type::string()),
                'description' => 'Monitor ID or label',
            ],
            'from' => [
                'type' => Type::int(),
                'description' => 'Start timestamp (optional)',
            ],
            'to' => [
                'type' => Type::int(),
                'description' => 'End timestamp (optional)',
            ],
        ];
    }

    public function resolve($root, $args)
    {
        $monitor = Monitor::where('label', $args['monitorIdentifier'])->firstOrFail();

        $logsQuery = $monitor->logs();

        if (!empty($args['from'])) {
            $logsQuery->where('created_at', '>=', date('Y-m-d H:i:s', $args['from']));
        }

        if (!empty($args['to'])) {
            $logsQuery->where('created_at', '<=', date('Y-m-d H:i:s', $args['to']));
        }

        $logs = $logsQuery->get();

        return $logs->map(function ($log) {
            return [
                'date' => $log->created_at,
                'ok' => $log->latest_status === 'succeeded',
                'responseTime' => $log->response_time,
            ];
        });
    }
}
