<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_redirects_guest_users_from_order_pages(): void
    {
        $order = Order::factory()->create();

        $this->get(route('orders.index'))
            ->assertRedirect(route('login'));

        $this->get(route('orders.show', $order->order_number))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function it_displays_only_the_authenticated_users_orders_in_history(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $oldOrder = Order::factory()->for($user)->create([
            'created_at' => now()->subDay(),
        ]);
        $newOrder = Order::factory()->for($user)->create([
            'created_at' => now(),
        ]);
        Order::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('orders.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('OrderHistory')
            ->has('orders', 2)
            ->where('orders.0.order_number', $newOrder->order_number)
            ->where('orders.1.order_number', $oldOrder->order_number)
            ->where('pagination.total', 2)
            ->where('pagination.current_page', 1)
            ->where('pagination.per_page', 10)
        );
    }

    #[Test]
    public function it_displays_order_details_for_the_order_owner(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create();
        $product = Product::factory()->create();
        OrderItem::factory()->forOrder($order)->forProduct($product)->create();

        $response = $this->actingAs($user)->get(route('orders.show', $order->order_number));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('OrderDetails')
            ->where('order.order_number', $order->order_number)
            ->has('order.items', 1)
            ->where('order.items.0.product.id', $product->id)
        );
    }

    #[Test]
    public function it_forbids_viewing_another_users_order_details(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        $this->actingAs($viewer)
            ->get(route('orders.show', $order->order_number))
            ->assertForbidden();
    }
}
