<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\CommerceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class CommerceController extends Controller
{
    public function marketplace(Request $request, ?string $category = null)
    {
        $query = Product::where('active', true);
        $kinds = ['kelas' => 'course', 'bundling' => 'bundle', 'langganan' => 'subscription', 'merchandise' => 'merchandise', 'preorder' => 'preorder'];
        if (isset($kinds[$category ?? ''])) {
            $query->where('kind', $kinds[$category]);
        }

        return Inertia::render('App', ['screen' => 'marketplace', 'data' => ['products' => $query->orderBy('price')->get(), 'category' => $category, 'coupons' => DB::table('coupons')->where('uses', '<', DB::raw('max_uses'))->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->get(['code', 'percent', 'min_seats'])]]);
    }

    public function start(Request $request, CommerceService $service)
    {
        $input = $request->validate(['product_id' => 'required|integer|exists:products,id', 'recipients' => 'sometimes|array|max:20', 'recipients.*' => 'email|max:190', 'coupon' => 'nullable|string|max:40']);
        $order = $service->createOrder($request->user(), $input['product_id'], $input['recipients'] ?? [], $input['coupon'] ?? null);
        if ($order->total === 0 && $order->status === 'pending') {
            $service->settle($order, 'free', 'free-order:'.$order->id);
        }

        return redirect('/checkout/'.$order->id);
    }

    public function checkout(Request $request, ?Order $order, CommerceService $service)
    {
        if (! $order) {
            return redirect('/marketplace')->with('error', 'Pilih produk untuk checkout.');
        }
        abort_unless($order->user_id === $request->user()->id, 403);
        if ($order->status === 'pending' && $order->expires_at->isPast()) {
            $service->cancel($order, 'expired');
            $order->refresh();
        }
        $token = null;
        $error = null;
        if ($order->status === 'pending') {
            try {
                $token = $service->snapToken($order);
            } catch (\Throwable $e) {
                report($e);
                $error = 'Layanan pembayaran belum merespons. Coba muat ulang nanti.';
            }
        }

        return Inertia::render('App', ['screen' => 'checkout', 'data' => ['order' => $order, 'items' => [$order->snapshot], 'recipients' => $order->recipients, 'snapToken' => $token, 'clientKey' => config('teras.midtrans.client_key'), 'snapUrl' => config('teras.midtrans.production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js', 'needsConfiguration' => ! config('teras.midtrans.server_key') || ! config('teras.midtrans.client_key'), 'paymentError' => $error]]);
    }

    public function orders(Request $request)
    {
        return Inertia::render('App', ['screen' => 'orders', 'data' => ['orders' => Order::where('user_id', $request->user()->id)->latest()->paginate(20)]]);
    }

    public function cancel(Request $request, Order $order, CommerceService $service)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $service->cancel($order);

        return back()->with('success', 'Status pesanan diperbarui.');
    }

    public function rewards(Request $request)
    {
        return Inertia::render('App', ['screen' => 'rewards', 'data' => ['items' => DB::table('reward_items')->get(), 'xp' => $request->user()->xpBalance(), 'bara' => $request->user()->baraBalance(), 'ledger' => DB::table('bara_entries')->where('user_id', $request->user()->id)->latest('id')->limit(50)->get(), 'redemptions' => DB::table('reward_redemptions')->where('user_id', $request->user()->id)->get()]]);
    }

    public function redeem(Request $request, int $id, CommerceService $service)
    {
        $request->validate(['request_key' => 'nullable|uuid']);
        $service->redeem($request->user(), $id, $request->request_key ?? hash('sha256', $request->user()->id.':'.$id));

        return back()->with('success', 'Bara ditukar. Lihat status reward pada riwayat.');
    }

    public function webhook(Request $request, CommerceService $service)
    {
        abort_unless(config('teras.midtrans.server_key'), 503);
        $input = $request->validate(['order_id' => 'required|string', 'status_code' => 'required|string', 'gross_amount' => 'required|numeric', 'signature_key' => 'required|string', 'transaction_status' => 'required|string']);
        $expected = hash('sha512', $input['order_id'].$input['status_code'].$input['gross_amount'].config('teras.midtrans.server_key'));
        abort_unless(hash_equals($expected, $input['signature_key']), 403);
        $order = Order::where('number', $input['order_id'])->firstOrFail();
        abort_unless((float) $input['gross_amount'] === $order->total * 1.0, 422);
        $url = (config('teras.midtrans.production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com').'/v2/'.rawurlencode($order->number).'/status';
        $status = Http::timeout(15)->withBasicAuth(config('teras.midtrans.server_key'), '')->get($url)->throw()->json();
        abort_unless(($status['order_id'] ?? null) === $order->number && (float) ($status['gross_amount'] ?? -1) === $order->total * 1.0, 422);
        $state = $status['transaction_status'] ?? '';
        if ($state === 'settlement' || ($state === 'capture' && ($status['fraud_status'] ?? null) === 'accept')) {
            $service->settle($order, $state, 'midtrans:'.($status['transaction_id'] ?? $order->number).':paid');
        } elseif ($state === 'refund') {
            $service->refundVerified($order, 'midtrans:'.($status['transaction_id'] ?? $order->number).':refund');
        } elseif (in_array($state, ['expire', 'cancel', 'deny'])) {
            $service->cancel($order, match ($state) {
                'expire' => 'expired','cancel' => 'cancelled',default => 'failed'
            });
        }

        return response()->json(['received' => true]);
    }

    public function adminOrders(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        return Inertia::render('App', ['screen' => $request->is('admin/settings') ? 'admin-settings' : 'admin-orders', 'data' => ['orders' => Order::latest()->paginate(30), 'products' => Product::all(), 'rewards' => DB::table('reward_redemptions')->latest('id')->limit(30)->get(), 'integrations' => ['midtrans' => (bool) config('teras.midtrans.server_key'), 'google' => (bool) config('services.google.client_id'), 'mail' => config('mail.default')]]]);
    }

    public function fulfill(Request $request, int $id)
    {
        abort_unless($request->user()->role === 'admin', 403);
        DB::table('reward_redemptions')->where('id', $id)->where('status', 'awaiting_fulfillment')->update(['status' => 'fulfilled', 'updated_at' => now()]);
        DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => 'reward.fulfilled', 'target' => (string) $id, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success','Reward ditandai telah dikirim.');
    }
}
