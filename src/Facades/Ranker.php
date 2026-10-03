<?php

namespace Ranker\Facades;

use Illuminate\Support\Facades\Facade;
use Ranker\Services\RankerManager;

/**
 * @method static void reorder(string|\Illuminate\Database\Eloquent\Model $model, array $ids, int $startOrder = 1, ?string $primaryKey = null)
 *
 * @see \Ranker\Services\RankerManager
 */
class Ranker extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-ranker';
    }
}
