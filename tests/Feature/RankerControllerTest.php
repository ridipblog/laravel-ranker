<?php

namespace Ranker\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Ranker\Http\Controllers\RankerController;
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
    public function it_validates_items_payload_requirement()
    {
        $response = $this->postJson('api/ranker/reorder', [
            'model' => Item::class,
            'items' => [],
        ]);

        $response->assertStatus(422);
    }
}
