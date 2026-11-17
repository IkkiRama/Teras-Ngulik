<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommerceService
{
    public function createOrder(User $buyer, int $productId, array $emails, ?string $couponCode): Order
    {
        return DB::transaction(function () use ($buyer, $productId, $emails, $couponCode) {
            User::whereKey($buyer->id)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($productId)->where('active', true)->lockForUpdate()->firstOrFail();
            $emails = array_map(fn ($email) => Str::lower(trim($email)), $emails);
            if (count($emails) !== count(array_unique($emails))) {
                throw ValidationException::withMessages(['recipients' => 'Email peserta tidak boleh berulang.']);
            }
            if (! $emails) {
                $emails = [$buyer->email];
            }
            if (in_array($product->kind, ['merchandise', 'preorder', 'subscription']) && count($emails) > 1) {
                throw ValidationException::withMessages(['recipients' => 'Produk ini hanya mendukung pembelian untuk satu akun.']);
            }
            $users = User::whereIn('email', $emails)->whereNotNull('email_verified_at')->get();
            if ($users->count() !== count($emails)) {
                throw ValidationException::withMessages(['recipients' => 'Periksa daftar peserta: semua peserta harus memiliki akun terdaftar dan email terverifikasi.']);
            }
            $courseIds = $product->kind === 'bundle' ? ($product->metadata['course_ids'] ?? []) : ($product->kind === 'course' ? [$product->course_id] : []);
            if (in_array($product->kind, ['course', 'bundle']) && ! array_filter($courseIds)) {
                throw ValidationException::withMessages(['product_id' => 'Produk belum memiliki kelas tujuan dan tidak dapat dibeli.']);
            }
            foreach ($users as $recipient) {
                if ($courseIds && DB::table('entitlements')->where('user_id', $recipient->id)->whereIn('course_id', $courseIds)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists()) {
                    throw ValidationException::withMessages(['recipients' => 'Salah satu peserta telah memiliki akses ke kelas dalam produk ini.']);
                }
            }
            $recipients = $users->map(fn ($u) => ['id' => $u->id, 'email' => $u->email])->sortBy('id')->values()->all();
            $pending = Order::where('user_id', $buyer->id)->where('product_id', $product->id)->where('status', 'pending')->where('expires_at', '>', now())->get()->first(fn ($o) => $o->recipients === $recipients);
            if ($pending) {
                $oldCode = $pending->coupon_id ? DB::table('coupons')->where('id', $pending->coupon_id)->value('code') : null;
                $newCode = $couponCode ? Str::upper(trim($couponCode)) : null;
                if ($oldCode === $newCode) {
                    return $pending;
                }
                $this->cancel($pending);
                $product->refresh();
            }
            $quantity = count($emails);
            if ($product->stock !== null && $product->stock < $quantity) {
                throw ValidationException::withMessages(['product_id' => 'Stok tidak cukup untuk seluruh peserta.']);
            }
            $subtotal = $product->price * $quantity;
            $discount = 0;
            $coupon = null;
            if ($couponCode) {
                $coupon = DB::table('coupons')->where('code', Str::upper(trim($couponCode)))->lockForUpdate()->first();
                if (! $coupon || $coupon->uses >= $coupon->max_uses || ($coupon->expires_at && now()->greaterThan($coupon->expires_at)) || $quantity < $coupon->min_seats) {
                    throw ValidationException::withMessages(['coupon' => 'Voucher tidak berlaku atau syarat jumlah peserta belum terpenuhi.']);
                }
                $discount = intdiv($subtotal * min(100, $coupon->percent), 100);
                DB::table('coupons')->where('id', $coupon->id)->increment('uses');
            }
            if ($product->stock !== null) {
                $product->decrement('stock', $quantity);
            }
            $order = Order::create(['number' => 'TN-'.now()->format('ymd').'-'.Str::upper(Str::random(10)), 'user_id' => $buyer->id, 'product_id' => $product->id, 'status' => 'pending', 'quantity' => $quantity, 'subtotal' => $subtotal, 'discount' => $discount, 'total' => $subtotal - $discount, 'recipients' => $recipients, 'snapshot' => ['title' => $product->title, 'kind' => $product->kind, 'unit_price' => $product->price, 'course_id' => $product->course_id, 'subject_id' => $product->subject_id, 'metadata' => $product->metadata ?? []], 'coupon_id' => $coupon?->id, 'expires_at' => now()->addHours(24)]);
            DB::table('audit_logs')->insert(['user_id' => $buyer->id, 'action' => 'order.created', 'target' => $order->number, 'metadata' => json_encode(['seats' => $quantity]), 'created_at' => now(), 'updated_at' => now()]);

            return $order;
        });
    }

    public function snapToken(Order $order): ?string
    {
        if ($order->snap_token) {
            return $order->snap_token;
        }
        if (! config('teras.midtrans.server_key') || ! config('teras.midtrans.client_key')) {
            return null;
        }
        if (in_array($order->snapshot['kind'], ['merchandise', 'preorder']) && ! $order->delivery) {
            return null;
        }
        $url = config('teras.midtrans.production') ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        $response = Http::timeout(15)->withBasicAuth(config('teras.midtrans.server_key'), '')->post($url, [
            'transaction_details' => ['order_id' => $order->number, 'gross_amount' => $order->total],
            'item_details' => [['id' => (string) $order->product_id, 'price' => $order->total, 'quantity' => 1, 'name' => Str::limit($order->snapshot['title'], 45, '')]],
            'customer_details' => ['first_name' => User::findOrFail($order->user_id)->name, 'email' => User::findOrFail($order->user_id)->email],
            'expiry' => ['unit' => 'hours', 'duration' => 24],
        ])->throw();
        $token = $response->json('token');
        if (! is_string($token) || ! $token) {
            throw new \RuntimeException('Token Midtrans tidak tersedia.');
        }
        $order->forceFill(['snap_token' => $token])->save();

        return $token;
    }

    public function settle(Order $input, string $providerStatus, string $eventKey): Order
    {
        return DB::transaction(function () use ($input, $providerStatus, $eventKey) {
            $order = Order::whereKey($input->id)->lockForUpdate()->firstOrFail();
            if (DB::table('payment_events')->where('event_key', $eventKey)->exists() || $order->status === 'paid') {
                return $order;
            }
            if (! in_array($order->status, ['pending'], true)) {
                throw ValidationException::withMessages(['payment' => 'Pesanan tidak lagi aktif; pembayaran memerlukan rekonsiliasi admin.']);
            }
            foreach ($order->recipients as $recipient) {
                $user = User::whereKey($recipient['id'])->whereNotNull('email_verified_at')->first();
                if (! $user || $user->email !== $recipient['email']) {
                    throw ValidationException::withMessages(['payment' => 'Validasi peserta gagal. Grant tidak diterbitkan.']);
                }
            }
            foreach ($order->recipients as $recipient) {
                $this->grant($recipient['id'], $order);
            }
            DB::table('payment_events')->insert(['order_id' => $order->id, 'event_key' => $eventKey, 'provider_status' => $providerStatus, 'metadata' => json_encode(['amount' => $order->total]), 'created_at' => now(), 'updated_at' => now()]);
            $order->update(['status' => 'paid', 'paid_at' => now()]);
            DB::table('audit_logs')->insert(['user_id' => null, 'action' => 'payment.verified', 'target' => $order->number, 'metadata' => json_encode(['recipients' => count($order->recipients)]), 'created_at' => now(), 'updated_at' => now()]);

            return $order;
        });
    }

    private function grant(int $userId, Order $order): void
    {
        $snapshot = $order->snapshot;
        $kind = $snapshot['kind'];
        $base = ['user_id' => $userId, 'source' => 'order', 'source_id' => (string) $order->id, 'created_at' => now(), 'updated_at' => now()];
        if (in_array($kind, ['course', 'bundle'])) {
            $ids = $kind === 'bundle' ? ($snapshot['metadata']['course_ids'] ?? []) : [$snapshot['course_id']];
            foreach (array_filter($ids) as $cid) {
                DB::table('entitlements')->insertOrIgnore([...$base, 'kind' => 'course', 'course_id' => $cid, 'subject_id' => null, 'expires_at' => null]);
            }
        } elseif ($kind === 'subscription') {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $currentEnd = DB::table('entitlements')->where('user_id', $userId)->where('kind', 'subscription')->where('expires_at', '>', now())->max('expires_at');
            $end = $currentEnd ? Carbon::parse($currentEnd) : now();
            DB::table('entitlements')->insert([...$base, 'kind' => 'subscription', 'course_id' => null, 'subject_id' => null, 'expires_at' => $end->addDays($snapshot['metadata']['days'] ?? 30)]);
        } elseif (in_array($kind, ['event', 'bootcamp', 'ebook', 'podcast', 'mentorship'])) {
            DB::table('entitlements')->insert([...$base, 'kind' => 'content', 'course_id' => null, 'subject_id' => $snapshot['subject_id'], 'expires_at' => null]);
        }
    }

    public function cancel(Order $input, string $status = 'cancelled'): void
    {
        DB::transaction(function () use ($input, $status) {
            $order = Order::whereKey($input->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'pending') {
                return;
            }
            $product = Product::whereKey($order->product_id)->lockForUpdate()->firstOrFail();
            if ($product->stock !== null) {
                $product->increment('stock', $order->quantity);
            }
            if ($order->coupon_id) {
                DB::table('coupons')->where('id', $order->coupon_id)->where('uses', '>', 0)->decrement('uses');
            }
            $order->update(['status' => $status]);
        });
    }

    public function refundVerified(Order $input, string $eventKey): void
    {
        DB::transaction(function () use ($input, $eventKey) {
            $order = Order::whereKey($input->id)->lockForUpdate()->firstOrFail();
            if ($order->status === 'refunded' || DB::table('payment_events')->where('event_key', $eventKey)->exists()) {
                return;
            }
            if ($order->status !== 'paid') {
                throw ValidationException::withMessages(['payment' => 'Refund perlu rekonsiliasi.']);
            }
            DB::table('entitlements')->where('source', 'order')->where('source_id', (string) $order->id)->update(['expires_at' => now(), 'updated_at' => now()]);
            $order->update(['status' => 'refunded']);
            DB::table('payment_events')->insert(['order_id' => $order->id, 'event_key' => $eventKey, 'provider_status' => 'refund', 'metadata' => json_encode(['amount' => $order->total]), 'created_at' => now(), 'updated_at' => now()]);
            DB::table('refund_requests')->where('order_id', $order->id)->update(['status' => 'completed', 'updated_at' => now()]);
            DB::table('audit_logs')->insert(['user_id' => null, 'action' => 'refund.verified', 'target' => $order->number, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function redeem(User $user, int $itemId, string $key): void
    {
        DB::transaction(function () use ($user, $itemId, $key) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (DB::table('reward_redemptions')->where('request_key', $key)->exists()) {
                return;
            }
            $item = DB::table('reward_items')->where('id', $itemId)->lockForUpdate()->firstOrFail();
            if ($item->stock < 1 || $user->baraBalance() < $item->bara_cost) {
                throw ValidationException::withMessages(['reward' => 'Bara belum cukup atau reward habis.']);
            }
            $id = DB::table('reward_redemptions')->insertGetId(['user_id' => $user->id, 'reward_item_id' => $itemId, 'request_key' => $key, 'cost' => $item->bara_cost, 'status' => $item->course_id ? 'granted' : 'awaiting_fulfillment', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('bara_entries')->insert(['user_id' => $user->id, 'amount' => -$item->bara_cost, 'source' => 'redemption', 'source_id' => (string) $id, 'idempotency_key' => 'redeem:'.$id, 'created_at' => now()]);
            DB::table('reward_items')->where('id', $itemId)->decrement('stock');
            if ($item->course_id) {
                DB::table('entitlements')->insert(['user_id' => $user->id, 'course_id' => $item->course_id, 'kind' => 'course', 'source' => 'reward', 'source_id' => (string) $id, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }
}
