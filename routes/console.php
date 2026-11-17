<?php

use App\Models\Order;
use App\Services\CommerceService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('teras:expire-orders', function () {
    $service = app(CommerceService::class);
    $count = 0;
    Order::where('status', 'pending')->where('expires_at', '<', now())->orderBy('id')->chunkById(100, function ($orders) use ($service, &$count) {
        foreach ($orders as $order) {
            $service->cancel($order, 'expired');
            $count++;
        }
    });
    $this->info($count.' pesanan kedaluwarsa diproses.');
});
Schedule::command('teras:expire-orders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
