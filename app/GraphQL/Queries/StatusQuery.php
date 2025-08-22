<?php

namespace App\GraphQL\Queries;

use App\Models\Monitor;
use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Facades\GraphQL;
use Rebing\GraphQL\Support\Query;

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
                'description' => 'Monitor ID',
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
        $monitor = Monitor::where('id', $args['monitorIdentifier'])->firstOrFail();

        $logsQuery = $monitor->logs();

        if (! empty($args['from'])) {
            $logsQuery->where('started_at', '>=', date('Y-m-d H:i:s', $args['from']));
        }

        if (! empty($args['to'])) {
            $logsQuery->where('started_at', '<=', date('Y-m-d H:i:s', $args['to']));
        }

        $logs = $logsQuery->get();

        return $logs->map(function ($log) {
            return [
                'date' => $log->started_at,
                'ok' => $log->status === 'succeeded',
                'responseTime' => $log->response_time_ms,
            ];
        })
            ->sortByDesc('date')
            ->values()
            ->toArray();
    }
}
