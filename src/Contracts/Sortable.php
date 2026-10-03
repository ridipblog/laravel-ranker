<?php

namespace Ranker\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Sortable
{
    /**
     * Get the name of the column that stores the sort order.
     */
    public function determineSortColumnName(): string;

    /**
     * Set a new order for a list of model IDs.
     *
     * @param array $ids
     * @param int $startOrder
     * @param string|null $primaryKey
     * @return void
     */
    public static function setNewOrder(array $ids, int $startOrder = 1, ?string $primaryKey = null): void;

    /**
     * Scope a query to only include items in their sorted order.
     *
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder;
}
