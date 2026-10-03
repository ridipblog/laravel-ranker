<?php

namespace Ranker\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ranker\Contracts\Sortable;
use Ranker\Traits\HasSortableOrder;

class SoftDeletedItem extends Model implements Sortable
{
    use HasSortableOrder, SoftDeletes;

    protected $table = 'soft_deleted_items';

    protected $guarded = [];

    public array $sortable = [
        'order_column_name' => 'order_column',
        'normalize_on_delete' => true,
        'restore_to' => 'end',
    ];
}
