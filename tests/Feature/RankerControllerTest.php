<?php

namespace Ranker\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Ranker\Http\Controllers\RankerController;
use Ranker\Tests\Models\CategorizedItem;
use Ranker\Tests\Models\Item;
use Ranker\Tests\TestCase;

class RankerControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::ranker('api/ranker/reorder');
    }

    /** @test */
    public function it_can_reorder_items_through_the_endpoint()
    {
        $item1 = Item::create(['name' => 'First']);
        $item2 = Item::create(['name' => 'Second']);
        $item3 = Item::create(['name' => 'Third']);

        $response = $this->postJson('api/ranker/reorder', [
            'model' => Item::class,
            'items' => [$item3->id, $item1->id, $item2->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'count' => 3,
            ]);

        $this->assertEquals(1, $item3->fresh()->order_column);
        $this->assertEquals(2, $item1->fresh()->order_column);
        $this->assertEquals(3, $item2->fresh()->order_column);
    }

    /** @test */
    public function it_can_move_items_across_groups_via_endpoint()
    {
        $cat1Item1 = CategorizedItem::create(['name' => 'Task A', 'category_id' => 1]);
        $cat1Item2 = CategorizedItem::create(['name' => 'Task B', 'category_id' => 1]);
        $cat2Item1 = CategorizedItem::create(['name' => 'Task C', 'category_id' => 2]);

        $response = $this->postJson('api/ranker/reorder', [
            'model' => CategorizedItem::class,
            'items' => [$cat1Item2->id],
            'target_group' => ['category_id' => 2],
            'position' => 1,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'item_id' => $cat1Item2->id,
            ]);

        $this->assertEquals(2, $cat1Item2->fresh()->category_id);
        $this->assertEquals(1, $cat1Item2->fresh()->custom_order);
        $this->assertEquals(2, $cat2Item1->fresh()->custom_order);
    }

    /** @test */
    public function it_validates_items_payload_requirement()
    {
        $response = $this->postJson('api/ranker/reorder', [
            'model' => Item::class,
            'items' => [],
        ]);

        $response->assertStatus(422);
    }
}
