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

    /**
     * Normalize gaps in the sort sequence (1, 2, 3...) for the model or scope.
     *
     * @param array $scope
     * @return void
     */
    public static function normalizeOrder(array $scope = []): void;

    /**
     * Move model to another category/group and assign position.
     *
     * @param array $attributes
     * @param int|null $newPosition
     * @return bool
     */
    public function moveToGroup(array $attributes, ?int $newPosition = null): bool;

    /**
     * Move model to a specific numerical position within its current group.
     *
     * @param int $targetPosition
     * @return bool
     */
    public function moveToPosition(int $targetPosition): bool;
}
