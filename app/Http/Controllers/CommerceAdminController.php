<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CommerceAdminController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }

    public function catalog(Request $request)
    {
        $this->authorizeAdmin($request);

        return Inertia::render('App', ['screen' => 'platform-catalog', 'data' => ['products' => Product::all(), 'coupons' => DB::table('coupons')->get(), 'courses' => Course::all(['id', 'title']), 'orders' => Order::where('status', 'paid')->latest()->limit(40)->get(), 'refunds' => DB::table('refund_requests')->join('orders', 'orders.id', '=', 'refund_requests.order_id')->select('refund_requests.*', 'orders.number')->latest('refund_requests.id')->limit(40)->get(), 'redemptions' => DB::table('reward_redemptions')->where('status', 'awaiting_fulfillment')->get()]]);
    }

    public function product(Request $request, ?Product $product = null)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['title' => 'required|string|max:150', 'slug' => ['required', 'regex:/^[a-z0-9-]+$/', Rule::unique('products')->ignore($product?->id)], 'description' => 'required|string|max:5000', 'kind' => 'required|in:course,bundle,subscription,merchandise,preorder,event,bootcamp,ebook,podcast,mentorship', 'price' => 'required|integer|min:0|max:100000000', 'stock' => 'nullable|integer|min:0|max:1000000', 'course_id' => 'nullable|exists:courses,id', 'subject_id' => 'nullable|exists:content_items,id', 'active' => 'required|boolean', 'course_ids' => 'sometimes|array|max:50', 'course_ids.*' => 'exists:courses,id', 'days' => 'nullable|integer|min:1|max:366']);
        if ($data['kind'] === 'course' && empty($data['course_id'])) {
            throw ValidationException::withMessages(['course_id' => 'Produk kelas memerlukan kelas tujuan.']);
        }
        if ($data['kind'] === 'bundle' && empty($data['course_ids'])) {
            throw ValidationException::withMessages(['course_ids' => 'Bundel memerlukan setidaknya satu kelas.']);
        }
        if (in_array($data['kind'], ['event', 'bootcamp', 'ebook', 'podcast', 'mentorship']) && empty($data['subject_id'])) {
            throw ValidationException::withMessages(['subject_id' => 'Pilih ID konten tujuan.']);
        }
        $data['metadata'] = ['course_ids' => $data['course_ids'] ?? [], 'days' => $data['days'] ?? 30];
        unset($data['course_ids'],$data['days']);
        $product ??= new Product;
        $product->fill($data)->save();
        $this->audit($request, 'product.saved', (string) $product->id);

        return back()->with('success', 'Produk disimpan. Harga pesanan sebelumnya tetap memakai snapshot.');
    }

    public function coupon(Request $request)
    {
        $this->authorizeAdmin($request);
        $input = $request->validate(['code' => 'required|regex:/^[A-Za-z0-9_-]{3,30}$/', 'percent' => 'required|integer|min:1|max:90', 'max_uses' => 'required|integer|min:1|max:100000', 'min_seats' => 'required|integer|min:1|max:20', 'expires_at' => 'nullable|date']);
        $input['code'] = Str::upper($input['code']);
        DB::table('coupons')->updateOrInsert(['code' => $input['code']], [...$input, 'updated_at' => now(), 'created_at' => now()]);
        $this->audit($request, 'coupon.saved', $input['code']);

        return back()->with('success', 'Voucher disimpan.');
    }

    public function delivery(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id && in_array($order->snapshot['kind'], ['merchandise', 'preorder']) && $order->status === 'pending', 403);
        $input = $request->validate(['name' => 'required|string|max:100', 'address' => 'required|string|max:1000', 'city' => 'required|string|max:100', 'postcode' => 'required|string|max:12', 'phone' => 'required|string|max:25']);
        $order->update(['delivery' => $input]);

        return back()->with('success', 'Alamat pengiriman tersimpan secara privat.');
    }

    public function fulfill(Request $request, Order $order)
    {
        $this->authorizeAdmin($request);
        abort_unless($order->status === 'paid' && in_array($order->snapshot['kind'], ['merchandise', 'preorder']), 422);
        $input = $request->validate(['tracking_number' => 'required|string|max:100']);
        $order->update([...$input, 'fulfillment_status' => 'shipped']);
        $this->audit($request, 'order.shipped', $order->number);

        return back()->with('success', 'Pengiriman dicatat.');
    }

    public function refundRequest(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id && $order->status === 'paid', 403);
        $input = $request->validate(['reason' => 'required|string|min:15|max:3000']);
        DB::table('refund_requests')->updateOrInsert(['order_id' => $order->id], ['user_id' => $request->user()->id, 'reason' => $input['reason'], 'status' => 'requested', 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Permintaan refund dicatat. Admin meninjau dan memproses melalui provider.');
    }

    public function refundReview(Request $request, int $id)
    {
        $this->authorizeAdmin($request);
        $input = $request->validate(['status' => 'required|in:requested,reviewing,rejected,provider_pending', 'admin_note' => 'required|string|max:3000']);
        DB::table('refund_requests')->where('id', $id)->update([...$input, 'updated_at' => now()]);
        $this->audit($request, 'refund.reviewed', (string) $id);

        return back()->with('success', 'Review refund disimpan. Status pembayaran hanya berubah dari provider.');
    }

    private function audit(Request $request, string $action, string $target): void
    {
        DB::table('audit_logs')->insert(['user_id' => $request->user()->id, 'action' => $action, 'target' => $target, 'created_at' => now(), 'updated_at' => now()]);
    }
}
