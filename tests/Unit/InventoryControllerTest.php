<?php

namespace Tests\Unit;


use App\Jobs\BuyIngredientJob;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use Mockery;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_success_with_default_parameters()
    {
        Inventory::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/inventory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ingredient', 'quantity', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_index_success_with_custom_parameters()
    {
        Inventory::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/inventory?take=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ingredient', 'quantity', 'created_at', 'updated_at'],
                ],
            ])->assertJsonCount(5, 'data');
    }

    public function test_show_success()
    {
        $inventory = Inventory::factory()->create();

        $response = $this->getJson('/api/v1/inventory/' . $inventory->ingredient);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'ingredient', 'quantity', 'created_at', 'updated_at']]);
    }

    public function test_show_inventory_not_found()
    {
        $response = $this->getJson('/api/v1/inventory/nonexistent_ingredient');

        $response->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    public function test_request_success()
    {
        Queue::fake();

        $inventory = Inventory::factory()->create(['quantity' => 10]);

        $response = $this->postJson('/api/v1/inventory/request', [
            'ingredients' => [
                [
                    'name' => $inventory->ingredient,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['message']]);

        $this->assertDatabaseHas('inventories', [
            'ingredient' => $inventory->ingredient,
            'quantity' => 5,
        ]);

        Queue::assertNotPushed(BuyIngredientJob::class);
    }

    public function test_request_insufficient_quantity()
    {
        Queue::fake();

        $inventory = Inventory::factory()->create(['quantity' => 3]);

        $response = $this->postJson('/api/v1/inventory/request', [
            'ingredients' => [
                [
                    'name' => $inventory->ingredient,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);

        Queue::assertPushed(BuyIngredientJob::class);
    }

    public function test_request_ingredient_not_found()
    {
        $response = $this->postJson('/api/v1/inventory/request', [
            'ingredients' => [
                [
                    'name' => 'nonexistent_ingredient',
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    public function test_request_validation_errors()
    {
        $response = $this->postJson('/api/v1/inventory/request', [
            'ingredients' => [
                [
                    'name' => '',
                    'quantity' => 0,
                ],
            ],
        ]);

        $response->assertStatus(400)
            ->assertJsonStructure(['message', 'data']);
    }

}