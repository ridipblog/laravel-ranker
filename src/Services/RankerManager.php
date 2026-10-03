<?php

namespace Ranker\Services;

use Illuminate\Database\Eloquent\Model;

class RankerManager
{
    /**
     * Reorder an array of model IDs.
     *
     * @param string|Model $model
     * @param array $ids
     * @param int $startOrder
     * @param string|null $primaryKey
     * @return void
     */
    public function reorder(string|Model $model, array $ids, int $startOrder = 1, ?string $primaryKey = null): void
    {
        $class = is_object($model) ? get_class($model) : $model;

        if (method_exists($class, 'setNewOrder')) {
            $class::setNewOrder($ids, $startOrder, $primaryKey);
        }
    }
}
