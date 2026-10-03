<?php

namespace Ranker\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Ranker\Events\ItemMoved;
use Ranker\Events\OrderChanged;
use Ranker\Tests\Models\CategorizedItem;
use Ranker\Tests\Models\Item;
use Ranker\Tests\TestCase;

class HasSortableOrderTest extends TestCase
{
    /** @test */
    public function it_automatically_assigns_an_order_when_creating_records()
    {
        $item1 = Item::create(['name' => 'First']);
        $item2 = Item::create(['name' => 'Second']);
        $item3 = Item::create(['name' => 'Third']);

        $this->assertEquals(1, $item1->order_column);
        $this->assertEquals(2, $item2->order_column);
        $this->assertEquals(3, $item3->order_column);
    }

    /** @test */
    public function it_can_batch_set_a_new_order_with_single_query()
    {
        Event::fake();

        $item1 = Item::create(['name' => 'Item 1']);
        $item2 = Item::create(['name' => 'Item 2']);
        $item3 = Item::create(['name' => 'Item 3']);

        // Reverse order: 3, 1, 2
        Item::setNewOrder([$item3->id, $item1->id, $item2->id]);

        $this->assertEquals(1, $item3->fresh()->order_column);
        $this->assertEquals(2, $item1->fresh()->order_column);
        $this->assertEquals(3, $item2->fresh()->order_column);

        Event::assertDispatched(OrderChanged::class);
    }

    /** @test */
    public function it_can_swap_order_between_two_models()
    {
        $item1 = Item::create(['name' => 'Item 1']);
        $item2 = Item::create(['name' => 'Item 2']);

        $item1->swapOrderWith($item2);

        $this->assertEquals(2, $item1->fresh()->order_column);
        $this->assertEquals(1, $item2->fresh()->order_column);
    }

    /** @test */
    public function it_can_move_an_item_up_and_down()
    {
        $item1 = Item::create(['name' => 'Item 1']); // 1
        $item2 = Item::create(['name' => 'Item 2']); // 2
        $item3 = Item::create(['name' => 'Item 3']); // 3

        $item2->moveOrderUp(); // item2 becomes 1, item1 becomes 2
        $this->assertEquals(1, $item2->fresh()->order_column);
        $this->assertEquals(2, $item1->fresh()->order_column);

        $item2->moveOrderDown(); // item2 becomes 2, item1 becomes 1
        $this->assertEquals(2, $item2->fresh()->order_column);
        $this->assertEquals(1, $item1->fresh()->order_column);
    }

    /** @test */
    public function it_can_move_an_item_to_specific_position()
    {
        Event::fake();

        $item1 = Item::create(['name' => 'Item 1']); // 1
        $item2 = Item::create(['name' => 'Item 2']); // 2
        $item3 = Item::create(['name' => 'Item 3']); // 3
        $item4 = Item::create(['name' => 'Item 4']); // 4

        // Move item4 to position 2
        $item4->moveToPosition(2);

        $this->assertEquals(1, $item1->fresh()->order_column);
        $this->assertEquals(2, $item4->fresh()->order_column);
        $this->assertEquals(3, $item2->fresh()->order_column);
        $this->assertEquals(4, $item3->fresh()->order_column);

        Event::assertDispatched(ItemMoved::class);
    }

    /** @test */
    public function it_can_move_an_item_to_start_and_end()
    {
        $item1 = Item::create(['name' => 'Item 1']); // 1
        $item2 = Item::create(['name' => 'Item 2']); // 2
        $item3 = Item::create(['name' => 'Item 3']); // 3

        $item3->moveToStart();
        $this->assertEquals(1, $item3->fresh()->order_column);
        $this->assertEquals(2, $item1->fresh()->order_column);

        $item3->moveToEnd();
        $this->assertEquals(3, $item3->fresh()->order_column);
    }

    /** @test */
    public function it_handles_group_by_scoped_ordering_properly()
    {
        $cat1Item1 = CategorizedItem::create(['name' => 'Cat 1 - Item 1', 'category_id' => 1]);
        $cat1Item2 = CategorizedItem::create(['name' => 'Cat 1 - Item 2', 'category_id' => 1]);

        $cat2Item1 = CategorizedItem::create(['name' => 'Cat 2 - Item 1', 'category_id' => 2]);
        $cat2Item2 = CategorizedItem::create(['name' => 'Cat 2 - Item 2', 'category_id' => 2]);

        $this->assertEquals(1, $cat1Item1->custom_order);
        $this->assertEquals(2, $cat1Item2->custom_order);

        // Group 2 should have its own sequence starting at 1
        $this->assertEquals(1, $cat2Item1->custom_order);
        $this->assertEquals(2, $cat2Item2->custom_order);
    }

    /** @test */
    public function it_can_move_items_across_groups_in_kanban_mode()
    {
        $cat1Item1 = CategorizedItem::create(['name' => 'Task A', 'category_id' => 1]); // 1
        $cat1Item2 = CategorizedItem::create(['name' => 'Task B', 'category_id' => 1]); // 2
        $cat1Item3 = CategorizedItem::create(['name' => 'Task C', 'category_id' => 1]); // 3

        $cat2Item1 = CategorizedItem::create(['name' => 'Task D', 'category_id' => 2]); // 1

        // Move Task B from Category 1 to Category 2 at position 1
        $cat1Item2->moveToGroup(['category_id' => 2], newPosition: 1);

        // Origin category 1 remaining items should shift down
        $this->assertEquals(1, $cat1Item1->fresh()->custom_order);
        $this->assertEquals(2, $cat1Item3->fresh()->custom_order);

        // Target category 2 should have Task B at 1 and Task D at 2
        $this->assertEquals(2, $cat1Item2->fresh()->category_id);
        $this->assertEquals(1, $cat1Item2->fresh()->custom_order);
        $this->assertEquals(2, $cat2Item1->fresh()->custom_order);
    }

    /** @test */
    public function it_can_normalize_order_gaps()
    {
        $item1 = Item::create(['name' => 'Item 1']); // 1
        $item2 = Item::create(['name' => 'Item 2']); // 2
        $item3 = Item::create(['name' => 'Item 3']); // 3

        // Manually create gaps: set orders to 10, 50, 90
        $item1->update(['order_column' => 10]);
        $item2->update(['order_column' => 50]);
        $item3->update(['order_column' => 90]);

        Item::normalizeOrder();

        $this->assertEquals(1, $item1->fresh()->order_column);
        $this->assertEquals(2, $item2->fresh()->order_column);
        $this->assertEquals(3, $item3->fresh()->order_column);
    }

    /** @test */
    public function it_handles_soft_deletion_normalization_and_restoration()
    {
        $item1 = \Ranker\Tests\Models\SoftDeletedItem::create(['name' => 'Item 1']); // 1
        $item2 = \Ranker\Tests\Models\SoftDeletedItem::create(['name' => 'Item 2']); // 2
        $item3 = \Ranker\Tests\Models\SoftDeletedItem::create(['name' => 'Item 3']); // 3

        // Soft delete item2 -> item3 should shift from 3 down to 2
        $item2->delete();

        $this->assertEquals(1, $item1->fresh()->order_column);
        $this->assertEquals(2, $item3->fresh()->order_column);

        // Restore item2 -> default 'restore_to' => 'end' puts it at order 3
        $item2->restore();

        $this->assertEquals(3, $item2->fresh()->order_column);
    }
}
