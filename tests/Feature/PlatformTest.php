<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CommerceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private function learner(array $attributes = []): User
    {
        return User::factory()->create([...$attributes, 'email_verified_at' => now()]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::create([...['slug' => 'course-product', 'title' => 'Demo product', 'kind' => 'course', 'course_id' => 1, 'price' => 100000, 'stock' => 10, 'description' => 'Test'], ...$attributes]);
    }

    public function test_registration_does_not_verify_email_or_subscribe_without_consent(): void
    {
        $this->post('/register', ['name' => 'Anak Ngulik', 'username' => 'TESTUSER', 'email' => 'tester@example.test', 'password' => 'StrongPass123', 'password_confirmation' => 'StrongPass123', 'age_band' => 'child'])->assertRedirect('/verify-email');
        $user = User::where('username', 'testuser')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('private', $user->privacy);
        $this->assertDatabaseCount('newsletter_subscriptions', 0);
        $this->actingAs($user)->post('/checkout', ['product_id' => 1])->assertRedirect('/verify-email');
    }

    public function test_profile_cannot_change_email_or_grant_admin_role(): void
    {
        $user = $this->learner(['email' => 'original@example.test']);
        $this->actingAs($user)->put('/dashboard/profil', ['name' => 'Updated', 'username' => 'updated_user', 'privacy' => 'public', 'locale' => 'id', 'email' => 'changed@example.test', 'role' => 'admin'])->assertRedirect();
        $this->assertSame('original@example.test', $user->fresh()->email);
        $this->assertSame('learner', $user->fresh()->role);
        $this->actingAs($user)->get('/admin/users')->assertForbidden();
        $this->actingAs($user)->get('/admin/orders')->assertForbidden();
    }

    public function test_password_change_requires_unexpired_one_use_otp_and_counts_failures(): void
    {
        Mail::fake();
        $user = $this->learner(['password' => 'OldPassword123']);
        $this->actingAs($user);
        $this->post('/account/password/otp', ['current_password' => 'incorrect'])->assertSessionHasErrors('current_password');
        $this->post('/account/password/otp', ['current_password' => 'OldPassword123'])->assertRedirect();
        $this->assertDatabaseCount('password_otps', 1);
        DB::table('password_otps')->where('user_id', $user->id)->update(['code_hash' => Hash::make('123456')]);
        $payload = ['otp' => '000000', 'password' => 'NewPassword456', 'password_confirmation' => 'NewPassword456'];
        $this->put('/account/password', $payload)->assertSessionHasErrors('otp');
        $this->assertSame(1, DB::table('password_otps')->value('attempts'));
        $payload['otp'] = '123456';
        $this->put('/account/password', $payload)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
        $this->put('/account/password', $payload)->assertSessionHasErrors('otp');
    }

    public function test_one_payment_grants_all_verified_group_recipients_exactly_once(): void
    {
        $buyer = $this->learner();
        $other = $this->learner();
        $product = $this->product();
        $service = app(CommerceService::class);
        $order = $service->createOrder($buyer, $product->id, [$buyer->email, $other->email], null);
        $this->assertSame(200000, $order->total);
        $this->assertSame(8, $product->fresh()->stock);
        $service->settle($order, 'settlement', 'tx:1');
        $service->settle($order, 'settlement', 'tx:1');
        $this->assertDatabaseCount('entitlements', 2);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertSame('paid', $order->fresh()->status);
        $service->cancel($order);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_invalid_group_has_no_partial_order_or_stock_changes(): void
    {
        $buyer = $this->learner();
        $product = $this->product();
        $response = $this->actingAs($buyer)->post('/checkout', ['product_id' => $product->id, 'recipients' => [$buyer->email, 'missing@example.test']]);
        $response->assertSessionHasErrors('recipients');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_failed_recipient_revalidation_rolls_back_entitlements(): void
    {
        $buyer = $this->learner();
        $other = $this->learner();
        $product = $this->product();
        $service = app(CommerceService::class);
        $order = $service->createOrder($buyer, $product->id, [$buyer->email, $other->email], null);
        $other->forceFill(['email_verified_at' => null])->save();
        try {
            $service->settle($order, 'settlement', 'tx:bad');
            $this->fail('Must reject invalid participant');
        } catch (ValidationException $e) {
        }
        $this->assertDatabaseCount('entitlements', 0);
        $this->assertDatabaseCount('payment_events', 0);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_cancel_releases_stock_and_coupon_once(): void
    {
        $buyer = $this->learner();
        $product = $this->product();
        $service = app(CommerceService::class);
        DB::table('coupons')->insert(['code' => 'TEST', 'percent' => 20, 'max_uses' => 1, 'min_seats' => 1, 'uses' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $order = $service->createOrder($buyer, $product->id, [], 'TEST');
        $this->assertSame(80000, $order->total);
        $service->cancel($order);
        $service->cancel($order);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(0, DB::table('coupons')->value('uses'));
    }

    public function test_midtrans_signature_status_check_and_replay_protection(): void
    {
        config(['teras.midtrans.server_key' => 'sandbox-test']);
        $buyer = $this->learner();
        $product = $this->product();
        $order = app(CommerceService::class)->createOrder($buyer, $product->id, [], null);
        $payload = ['order_id' => $order->number, 'status_code' => '200', 'gross_amount' => '100000.00', 'transaction_status' => 'settlement', 'signature_key' => 'bad'];
        $this->postJson('/webhooks/midtrans', $payload)->assertForbidden();
        $this->assertDatabaseCount('entitlements', 0);
        $payload['signature_key'] = hash('sha512', $order->number.'200100000.00sandbox-test');
        Http::fake(['api.sandbox.midtrans.com/*' => Http::response(['order_id' => $order->number, 'gross_amount' => '100000.00', 'transaction_status' => 'settlement', 'transaction_id' => 'one'])]);
        $this->postJson('/webhooks/midtrans', $payload)->assertOk();
        $this->postJson('/webhooks/midtrans', $payload)->assertOk();
        $this->assertDatabaseCount('entitlements', 1);
    }

    public function test_reward_spends_only_bara_and_replay_does_not_double_debit(): void
    {
        $user = $this->learner();
        $item = DB::table('reward_items')->insertGetId(['title' => 'Reward', 'description' => 'Test', 'bara_cost' => 20, 'stock' => 2, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('xp_entries')->insert(['user_id' => $user->id, 'amount' => 1000, 'source' => 'test', 'source_id' => '1', 'idempotency_key' => 'test-xp']);
        $this->actingAs($user)->post('/rewards/'.$item.'/redeem')->assertSessionHasErrors('reward');
        DB::table('bara_entries')->insert(['user_id' => $user->id, 'amount' => 50, 'source' => 'test', 'source_id' => '1', 'idempotency_key' => 'test-bara']);
        $this->post('/rewards/'.$item.'/redeem')->assertSessionHasNoErrors();
        $this->post('/rewards/'.$item.'/redeem')->assertSessionHasNoErrors();
        $this->assertSame(30,$user->baraBalance());
        $this->assertSame(1000,$user->xpBalance());
        $this->assertDatabaseCount('reward_redemptions',1);
    }
}
