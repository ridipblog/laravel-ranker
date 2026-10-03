<?php

namespace Ranker\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Ranker\Events\ItemMoved;
use Ranker\Events\OrderChanged;

trait HasSortableOrder
{
    /**
     * Boot the trait and register Eloquent event hooks.
     */
    public static function bootHasSortableOrder(): void
    {
        static::creating(function (Model $model) {
            $shouldSort = $model->sortable['sort_when_creating'] 
                ?? config('ranker.sort_when_creating', true);

            $column = $model->determineSortColumnName();

            if ($shouldSort && is_null($model->getAttribute($column))) {
                $model->setAttribute($column, $model->getHighestOrderNumber() + 1);
            }
        });

        static::deleted(function (Model $model) {
            $shouldNormalize = $model->sortable['normalize_on_delete'] 
                ?? config('ranker.normalize_on_delete', false);

            if ($shouldNormalize) {
                $column = $model->determineSortColumnName();
                $deletedOrder = $model->getAttribute($column);

                if (!is_null($deletedOrder)) {
                    $model->buildSortScopeQuery()
                        ->where($column, '>', $deletedOrder)
                        ->decrement($column);
                }
            }
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                $column = $model->determineSortColumnName();
                $restoreMode = $model->sortable['restore_to'] 
                    ?? config('ranker.restore_to', 'end');

                $startOrder = config('ranker.start_order', 1);

                if ($restoreMode === 'start') {
                    $model->buildSortScopeQuery()
                        ->where($column, '>=', $startOrder)
                        ->increment($column);
                    $model->setAttribute($column, $startOrder);
                    $model->saveQuietly();
                } elseif ($restoreMode === 'original' && !is_null($model->getAttribute($column))) {
                    $origOrder = $model->getAttribute($column);
                    $model->buildSortScopeQuery()
                        ->where($column, '>=', $origOrder)
                        ->increment($column);
                    $model->saveQuietly();
                } else {
                    // 'end' default: place at the end of the active group
                    $highest = $model->getHighestOrderNumber();
                    $model->setAttribute($column, $highest + 1);
                    $model->saveQuietly();
                }
            });
        }
    }

    /**
     * Determine the column name used for ordering.
     */
    public function determineSortColumnName(): string
    {
        return $this->sortable['order_column_name'] 
            ?? config('ranker.order_column_name', 'order_column');
    }

    /**
     * Determine custom grouping/scope columns (e.g. category_id, project_id).
     */
    public function determineGroupingColumns(): array
    {
        return (array) ($this->sortable['group_by'] ?? []);
    }

    /**
     * Get a query scoped to the current item's group/category if applicable.
     */
    public function buildSortScopeQuery(array $customScope = []): Builder
    {
        $query = static::query();

        $scopeColumns = !empty($customScope) 
            ? array_keys($customScope) 
            : $this->determineGroupingColumns();

        foreach ($scopeColumns as $column) {
            $value = array_key_exists($column, $customScope)
                ? $customScope[$column]
                : $this->getAttribute($column);

            $query->where($column, $value);
        }

        return $query;
    }

    /**
     * Get the highest order number currently in the database for this model/group.
     */
    public function getHighestOrderNumber(array $customScope = []): int
    {
        $column = $this->determineSortColumnName();
        $startOrder = config('ranker.start_order', 1);

        $max = $this->buildSortScopeQuery($customScope)->max($column);

        return is_null($max) ? ($startOrder - 1) : (int) $max;
    }

    /**
     * Scope a query to sort records in ascending or descending order.
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy($this->determineSortColumnName(), $direction);
    }

    /**
     * Scope a query to sort records in descending order.
     */
    public function scopeOrderedDesc(Builder $query): Builder
    {
        return $query->orderBy($this->determineSortColumnName(), 'desc');
    }

    /**
     * Perform a high-performance batch update of sort orders using a single SQL query.
     *
     * @param array $ids List of model IDs in the new desired sequence
     * @param int $startOrder Starting sequence number
     * @param string|null $primaryKey Column name for the primary key
     * @param array $scope Optional scope context
     * @return void
     */
    public static function setNewOrder(array $ids, int $startOrder = 1, ?string $primaryKey = null, array $scope = []): void
    {
        if (empty($ids)) {
            return;
        }

        $ids = array_values($ids);
        $instance = new static();
        $key = $primaryKey ?? $instance->getKeyName();
        $column = $instance->determineSortColumnName();
        $table = $instance->getTable();

        $cases = [];
        $params = [];

        foreach ($ids as $index => $id) {
            $order = $index + $startOrder;
            $cases[] = "WHEN {$key} = ? THEN ?";
            $params[] = $id;
            $params[] = $order;
        }

        $casesSql = implode(' ', $cases);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($params, $ids);

        DB::update(
            "UPDATE {$table} SET {$column} = CASE {$casesSql} END WHERE {$key} IN ({$placeholders})",
            $params
        );

        event(new OrderChanged(static::class, $ids, $scope));
    }

    /**
     * Normalize gaps in the sort sequence (1, 2, 3...) for the model or group.
     */
    public static function normalizeOrder(array $scope = []): void
    {
        $instance = new static();
        $key = $instance->getKeyName();
        $startOrder = config('ranker.start_order', 1);

        $query = $instance->buildSortScopeQuery($scope)->ordered();
        $ids = $query->pluck($key)->all();

        if (!empty($ids)) {
            static::setNewOrder($ids, $startOrder, $key, $scope);
        }
    }

    /**
     * Move the model to a specific numerical position within its current group.
     */
    public function moveToPosition(int $targetPosition): bool
    {
        $column = $this->determineSortColumnName();
        $currentPosition = (int) $this->getAttribute($column);

        if ($currentPosition === $targetPosition) {
            return true;
        }

        return DB::transaction(function () use ($column, $currentPosition, $targetPosition) {
            if ($targetPosition < $currentPosition) {
                // Moving up: shift down records in between
                $this->buildSortScopeQuery()
                    ->whereBetween($column, [$targetPosition, $currentPosition - 1])
                    ->increment($column);
            } else {
                // Moving down: shift up records in between
                $this->buildSortScopeQuery()
                    ->whereBetween($column, [$currentPosition + 1, $targetPosition])
                    ->decrement($column);
            }

            $this->setAttribute($column, $targetPosition);
            $saved = $this->save();

            if ($saved) {
                event(new ItemMoved($this, $currentPosition, $targetPosition));
            }

            return $saved;
        });
    }

    /**
     * Move model to another category/group and assign new position (Kanban boards).
     */
    public function moveToGroup(array $attributes, ?int $newPosition = null): bool
    {
        return DB::transaction(function () use ($attributes, $newPosition) {
            $column = $this->determineSortColumnName();
            $oldPosition = (int) $this->getAttribute($column);

            // 1. Shift remaining items in the origin group
            $this->buildSortScopeQuery()
                ->where($column, '>', $oldPosition)
                ->decrement($column);

            // 2. Determine position in the target group
            $startOrder = config('ranker.start_order', 1);
            $targetMax = $this->getHighestOrderNumber($attributes);

            if (is_null($newPosition) || $newPosition > ($targetMax + 1)) {
                $resolvedPosition = $targetMax + 1;
            } else {
                $resolvedPosition = max($startOrder, $newPosition);
                // Shift target group items at and above the new position
                $this->buildSortScopeQuery($attributes)
                    ->where($column, '>=', $resolvedPosition)
                    ->increment($column);
            }

            // 3. Update model with target group attributes and new order
            foreach ($attributes as $key => $value) {
                $this->setAttribute($key, $value);
            }
            $this->setAttribute($column, $resolvedPosition);

            $saved = $this->save();

            if ($saved) {
                event(new ItemMoved($this, $oldPosition, $resolvedPosition));
            }

            return $saved;
        });
    }

    /**
     * Swap the sort order of this model with another model.
     */
    public function swapOrderWith(Model $other): bool
    {
        $column = $this->determineSortColumnName();

        $myOrder = $this->getAttribute($column);
        $otherOrder = $other->getAttribute($column);

        $this->setAttribute($column, $otherOrder);
        $other->setAttribute($column, $myOrder);

        return DB::transaction(function () use ($other, $myOrder, $otherOrder) {
            $saved = $this->save() && $other->save();
            if ($saved) {
                event(new ItemMoved($this, $myOrder, $otherOrder));
                event(new ItemMoved($other, $otherOrder, $myOrder));
            }
            return $saved;
        });
    }

    /**
     * Move the model up one position in the order list.
     */
    public function moveOrderUp(): bool
    {
        $column = $this->determineSortColumnName();

        $previous = $this->buildSortScopeQuery()
            ->where($column, '<', $this->getAttribute($column))
            ->orderBy($column, 'desc')
            ->first();

        if ($previous) {
            return $this->swapOrderWith($previous);
        }

        return false;
    }

    /**
     * Move the model down one position in the order list.
     */
    public function moveOrderDown(): bool
    {
        $column = $this->determineSortColumnName();

        $next = $this->buildSortScopeQuery()
            ->where($column, '>', $this->getAttribute($column))
            ->orderBy($column, 'asc')
            ->first();

        if ($next) {
            return $this->swapOrderWith($next);
        }

        return false;
    }

    /**
     * Move the model to the absolute start of the sequence.
     */
    public function moveToStart(): bool
    {
        $startOrder = config('ranker.start_order', 1);
        return $this->moveToPosition($startOrder);
    }

    /**
     * Move the model to the absolute end of the sequence.
     */
    public function moveToEnd(): bool
    {
        $max = $this->getHighestOrderNumber();
        return $this->moveToPosition($max);
    }
}
