<?php

namespace Ranker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $modelClass The class name of the ordered model
     * @param array $ids The reordered array of model IDs
     * @param array $scope The grouping/scope attributes if applicable
     */
    public function __construct(
        public string $modelClass,
        public array $ids,
        public array $scope = []
    ) {}
}
