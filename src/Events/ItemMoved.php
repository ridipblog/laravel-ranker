<?php

namespace Ranker\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemMoved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Model $model The model that was moved
     * @param int $previousPosition The previous sequence index
     * @param int $newPosition The new sequence index
     */
    public function __construct(
        public Model $model,
        public int $previousPosition,
        public int $newPosition
    ) {}
}
