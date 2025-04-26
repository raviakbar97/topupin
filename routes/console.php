<?php

use App\Jobs\SyncDitusiProducts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ditusi:sync-products', function () {
    $this->info('Syncing Ditusi products...');
    dispatch(new SyncDitusiProducts());
})->purpose('Sync products from Ditusi API');

// Schedule to run every hour
Schedule::command('ditusi:sync-products')->hourly();
