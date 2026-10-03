<?php

namespace Ranker\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
    public function buildSortScopeQuery(): Builder
    {
        $query = static::query();

        foreach ($this->determineGroupingColumns() as $column) {
            $query->where($column, $this->getAttribute($column));
        }

        return $query;
    }

    /**
     * Get the highest order number currently in the database for this model/group.
     */
    public function getHighestOrderNumber(): int
    {
        $column = $this->determineSortColumnName();
        $startOrder = config('ranker.start_order', 1);

        $max = $this->buildSortScopeQuery()->max($column);

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
     * @return void
     */
    public static function setNewOrder(array $ids, int $startOrder = 1, ?string $primaryKey = null): void
    {
        if (empty($ids)) {
            return;
        }

        // Re-index array sequentially from 0
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

        return DB::transaction(function () use ($other) {
            return $this->save() && $other->save();
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
        $column = $this->determineSortColumnName();
        $min = $this->buildSortScopeQuery()->min($column);

        if ($this->getAttribute($column) === $min) {
            return true;
        }

        $this->setAttribute($column, ($min ?? 1) - 1);
        return $this->save();
    }

    /**
     * Move the model to the absolute end of the sequence.
     */
    public function moveToEnd(): bool
    {
        $column = $this->determineSortColumnName();
        $max = $this->buildSortScopeQuery()->max($column);

        if ($this->getAttribute($column) === $max) {
            return true;
        }

        $this->setAttribute($column, ($max ?? 0) + 1);
        return $this->save();
    }
}
