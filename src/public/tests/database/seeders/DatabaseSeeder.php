<?php

namespace Database\Seeders;

use App\Models\Counter;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@papyrusphoto.test',
            'role' => 'admin',
        ]);

        // Satu lokasi: studio Papyrus Photo
        $studio = Location::create([
            'name' => 'Papyrus Photo Jogja',
            'code' => 'PP',
            'description' => 'Studio cetak & sesi foto',
            'address' => 'Yogyakarta',
            'average_service_time' => 10,
            'open_time' => '09:00',
            'close_time' => '21:00',
        ]);

        // 2 loket: CS & Kasir (registrasi/order + pembayaran digabung) dan Fotografer.
        $csKasir = Counter::create([
            'location_id' => $studio->id,
            'name' => 'CS & Kasir',
            'stages' => ['registration', 'payment'],
        ]);

        $fotografer = Counter::create([
            'location_id' => $studio->id,
            'name' => 'Fotografer',
            'stages' => ['photo_session'],
        ]);

        // Staff: role menentukan langsung loket mereka (tanpa perlu pilih loket
        // manual) — role "operator" = CS & Kasir, role "fotografer" = sesi foto.
        User::factory()->create([
            'name' => 'CS & Kasir',
            'email' => 'cs1@papyrusphoto.test',
            'role' => 'operator',
            'location_id' => $studio->id,
        ]);

        User::factory()->create([
            'name' => 'Fotografer',
            'email' => 'fotografer@papyrusphoto.test',
            'role' => 'fotografer',
            'location_id' => $studio->id,
        ]);
    }
}
