<?php

namespace Ranker\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Ranker\Contracts\Sortable;
use Ranker\Traits\HasSortableOrder;

class CategorizedItem extends Model implements Sortable
{
    use HasSortableOrder;

    protected $table = 'categorized_items';

    protected $guarded = [];

    public array $sortable = [
        'order_column_name' => 'custom_order',
        'group_by' => ['category_id'],
    ];
}
