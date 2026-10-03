<?php

namespace Ranker\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Ranker\Contracts\Sortable;
use Ranker\Traits\HasSortableOrder;

class Item extends Model implements Sortable
{
    use HasSortableOrder;

    protected $table = 'items';

    protected $guarded = [];
}
