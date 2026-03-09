<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TransactionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Matikan Vite agar tidak mencari manifest.json saat testing
        $this->withoutVite();
    }

    public function test_user_can_create_transaction()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'price' => 15000,
            'stock' => 10,
        ]);

        $data = [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 3,
        ];

        $response = $this->postJson('/api/transactions', $data);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => ['id', 'user_id', 'product_id', 'quantity', 'total_price', 'created_at']
                 ]);

        $this->assertDatabaseHas('transactions', [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 3,
            'total_price' => 45000,
        ]);

        $product->refresh();
        $this->assertEquals(7, $product->stock);
    }

    public function test_transaction_fails_if_insufficient_stock()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 2]);

        $data = [
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'quantity'   => 5,
        ];

        $response = $this->postJson('/api/transactions', $data);

        $response->assertStatus(422);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_user_can_view_all_transactions()
    {
        Transaction::factory()->count(10)->create();

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
                 ->assertJsonCount(10, 'data');
    }

    public function test_user_can_filter_transactions_by_user()
    {
        $user = User::factory()->create();
        Transaction::factory()->count(3)->create(['user_id' => $user->id]);
        Transaction::factory()->count(2)->create();

        $response = $this->getJson('/api/transactions?user_id=' . $user->id);

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_transaction_detail()
    {
        $transaction = Transaction::factory()->create();

        $response = $this->getJson('/api/transactions/' . $transaction->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'data' => [
                         'id'         => $transaction->id,
                         'user_id'    => $transaction->user_id,
                         'product_id' => $transaction->product_id,
                         'quantity'   => $transaction->quantity,
                         'total_price'=> $transaction->total_price,
                     ]
                 ]);
    }

    public function test_transaction_not_found()
    {
        $response = $this->getJson('/api/transactions/999');

        $response->assertStatus(404);
    }
}