<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommerceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function buyer(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_homepage_never_serializes_premium_bodies(): void
    {
        ContentItem::create(['kind' => 'article', 'slug' => 'private-article', 'title' => 'Premium', 'summary' => 'Safe teaser', 'body' => 'ULTRA_PRIVATE_CONTENT', 'access' => 'subscribers', 'status' => 'published']);
        $response = $this->get('/');
        $response->assertOk();
        $response->assertDontSee('ULTRA_PRIVATE_CONTENT');
    }

    public function test_inventory_admin_permissions_and_order_price_snapshot(): void
    {
        $buyer = $this->buyer();
        $admin = $this->buyer();
        $admin->forceFill(['role' => 'admin'])->save();
        $payload = ['title' => 'Notebook', 'slug' => 'notebook', 'description' => 'Example', 'kind' => 'merchandise', 'price' => 45000, 'stock' => 10, 'active' => true];
        $this->actingAs($buyer)->post('/admin/products', $payload)->assertForbidden();
        $this->actingAs($admin)->post('/admin/products', $payload)->assertSessionHasNoErrors();
        $product = Product::firstOrFail();
        $order = app(CommerceService::class)->createOrder($buyer, $product->id, [], null);
        $payload['price'] = 99000;
        $this->put('/admin/products/'.$product->id, $payload)->assertSessionHasNoErrors();
        $this->assertSame(45000, $order->fresh()->total);
        $this->assertSame(45000, $order->fresh()->snapshot['unit_price']);
    }

    public function test_delivery_is_owner_scoped_and_stored_encrypted(): void
    {
        $owner = $this->buyer();
        $other = $this->buyer();
        $p = Product::create(['title' => 'Notebook', 'slug' => 'notebook', 'description' => 'Example', 'kind' => 'merchandise', 'price' => 45000]);
        $order = app(CommerceService::class)->createOrder($owner, $p->id, [], null);
        $address = ['name' => 'Example Recipient', 'address' => 'PRIVATE ADDRESS 123', 'city' => 'Bandung', 'postcode' => '40111', 'phone' => '0810000000'];
        $this->actingAs($other)->put('/orders/'.$order->id.'/delivery', $address)->assertForbidden();
        $this->actingAs($owner)->put('/orders/'.$order->id.'/delivery', $address)->assertSessionHasNoErrors();
        $raw = DB::table('orders')->where('id', $order->id)->value('delivery');
        $this->assertStringNotContainsString('PRIVATE ADDRESS', $raw);
        $this->assertSame($address, $order->fresh()->delivery);
    }

    public function test_verified_full_refund_revokes_only_its_source_entitlements(): void
    {
        $owner = $this->buyer();
        $p = Product::create(['title' => 'Course', 'slug' => 'course', 'description' => 'Example', 'kind' => 'course', 'price' => 45000, 'course_id' => 1]);
        $service = app(CommerceService::class);
        $order = $service->createOrder($owner, $p->id, [], null);
        $service->settle($order, 'settlement', 'test:paid');
        DB::table('entitlements')->insert(['user_id' => $owner->id, 'course_id' => 1, 'kind' => 'course', 'source' => 'reward', 'source_id' => '99', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($owner)->post('/orders/'.$order->id.'/refund', ['reason' => 'Saya meminta peninjauan refund untuk pesanan ini.'])->assertSessionHasNoErrors();
        $service->refundVerified($order, 'test:refund');
        $service->refundVerified($order, 'test:refund');
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertNotNull(DB::table('entitlements')->where('source', 'order')->value('expires_at'));
        $this->assertNull(DB::table('entitlements')->where('source', 'reward')->value('expires_at'));
        $this->assertSame('completed', DB::table('refund_requests')->value('status'));
        $this->assertDatabaseCount('payment_events', 2);
    }

    public function test_free_order_grants_without_claiming_provider_payment(): void
    {
        $owner = $this->buyer();
        $p = Product::create(['title' => 'Free course', 'slug' => 'free-course', 'description' => 'Example', 'kind' => 'course', 'price' => 0, 'course_id' => 1]);
        $this->actingAs($owner)->post('/checkout', ['product_id' => $p->id])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('paid', $order->status);
        $this->assertDatabaseHas('payment_events', ['provider_status' => 'free']);
        $this->assertDatabaseCount('entitlements', 1);
    }

    public function test_subscription_manual_renewal_extends_existing_end(): void
    {
        $owner = $this->buyer();
        $p = Product::create(['title' => 'Pro', 'slug' => 'pro', 'description' => 'Example', 'kind' => 'subscription', 'price' => 59000, 'metadata' => ['days' => 30]]);
        $current = now()->addDays(15);
        DB::table('entitlements')->insert(['user_id' => $owner->id, 'kind' => 'subscription', 'source' => 'old', 'source_id' => '1', 'expires_at' => $current]);
        $service = app(CommerceService::class);
        $order = $service->createOrder($owner, $p->id, [], null);
        $service->settle($order, 'settlement', 'renewal');
        $renewal = DB::table('entitlements')->where('source', 'order')->value('expires_at');
        $this->assertSame($current->addDays(30)->format('Y-m-d H:i:s'), $renewal);
    }

    public function test_empty_bundle_is_not_chargeable_and_owned_class_rejected(): void
    {
        $owner = $this->buyer();
        $p = Product::create(['title' => 'Empty bundle', 'slug' => 'empty', 'description' => 'Example', 'kind' => 'bundle', 'price' => 50000, 'metadata' => ['course_ids' => []]]);
        $this->actingAs($owner)->post('/checkout', ['product_id' => $p->id])->assertSessionHasErrors('product_id');
        $this->assertDatabaseCount('orders', 0);
        $p->update(['kind' => 'course', 'course_id' => 1]);
        DB::table('entitlements')->insert(['user_id' => $owner->id, 'course_id' => 1, 'kind' => 'course', 'source' => 'existing', 'source_id' => '1']);
        $this->post('/checkout',['product_id' => $p->id])->assertSessionHasErrors('recipients');
        $this->assertDatabaseCount('orders',0);
    }
}
