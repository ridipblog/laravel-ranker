<?php

namespace Ranker\Tests\Unit;

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
        $item1 = Item::create(['name' => 'Item 1']);
        $item2 = Item::create(['name' => 'Item 2']);
        $item3 = Item::create(['name' => 'Item 3']);

        // Reverse order: 3, 1, 2
        Item::setNewOrder([$item3->id, $item1->id, $item2->id]);

        $this->assertEquals(1, $item3->fresh()->order_column);
        $this->assertEquals(2, $item1->fresh()->order_column);
        $this->assertEquals(3, $item2->fresh()->order_column);
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
    public function it_can_move_an_item_to_start_and_end()
    {
        $item1 = Item::create(['name' => 'Item 1']); // 1
        $item2 = Item::create(['name' => 'Item 2']); // 2
        $item3 = Item::create(['name' => 'Item 3']); // 3

        $item3->moveToStart();
        $this->assertTrue($item3->fresh()->order_column < $item1->fresh()->order_column);

        $item1->moveToEnd();
        $this->assertTrue($item1->fresh()->order_column > $item2->fresh()->order_column);
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
}
