<?php

use App\Models\FlashSale;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    FlashSale::where('status', 'scheduled')
        ->where('start_time', '<=', now())
        ->update(['status' => 'active']);

    FlashSale::where('status', 'active')
        ->where('end_time', '<=', now())
        ->update(['status' => 'inactive']);
})->everyMinute();