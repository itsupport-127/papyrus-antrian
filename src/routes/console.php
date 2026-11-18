<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reset antrian setiap hari pukul 00:05
Schedule::command('queue:reset-daily')->dailyAt('00:05');

// Akhir hari: reservasi yang tidak check-in -> batal otomatis (tanpa keterangan) / ditandai perlu dicek (ada keterangan)
Schedule::command('bookings:close-day')->dailyAt('23:55');
