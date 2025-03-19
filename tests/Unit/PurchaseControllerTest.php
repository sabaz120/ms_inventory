<?php

namespace Tests\Unit;

use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class PurchaseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_success_with_default_parameters()
    {
        Purchase::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/purchases');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_index_success_with_custom_parameters()
    {
        Purchase::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/purchases?take=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'created_at', 'updated_at'],
                ],
            ])->assertJsonCount(5, 'data');
    }

}