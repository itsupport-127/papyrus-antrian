<?php

namespace Database\Seeders;

use App\Models\Counter;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ==========================
        // Admin
        // ==========================
        User::updateOrCreate(
            ['email' => 'admin@papyrusphoto.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'location_id' => null,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        // ==========================
        // Lokasi 1 - Studio (PP)
        // ==========================
        $studio = Location::updateOrCreate(
            ['code' => 'PP'],
            [
                'name' => 'Papyrus Photo Jogja',
                'description' => 'Studio cetak & sesi foto',
                'address' => 'Yogyakarta',
                'is_active' => true,
                'average_service_time' => 10,
                'open_time' => '09:00:00',
                'close_time' => '21:00:00',
                'booking_mode' => 'free',
                'service_type' => 'ticket',
            ]
        );

        // ==========================
        // Lokasi 2 - Reservasi (BK)
        // ==========================
        $booking = Location::updateOrCreate(
            ['code' => 'BK'],
            [
                'name' => 'Reservasi Papyrus Photo Jogja',
                'description' => 'Reservasi Studio cetak & sesi foto',
                'address' => 'Yogyakarta',
                'is_active' => true,
                'average_service_time' => 5,
                'open_time' => '09:00:00',
                'close_time' => '21:00:00',
                'booking_mode' => 'slots',
                'service_type' => 'booking',
            ]
        );

        // ==========================
        // Counter Studio (PP)
        // ==========================
        Counter::updateOrCreate(
            [
                'location_id' => $studio->id,
                'name' => 'CS & Kasir',
            ],
            [
                'stages' => ['registration', 'payment'],
            ]
        );

        $counterFotografer1 = Counter::updateOrCreate(
            [
                'location_id' => $studio->id,
                'name' => 'Studio 1',
            ],
            [
                'stages' => ['photo_session'],
            ]
        );

        $counterFotografer2 = Counter::updateOrCreate(
            [
                'location_id' => $studio->id,
                'name' => 'Studio 2',
            ],
            [
                'stages' => ['photo_session'],
            ]
        );

        $counterFotografer3 = Counter::updateOrCreate(
            [
                'location_id' => $studio->id,
                'name' => 'Studio 3',
            ],
            [
                'stages' => ['photo_session'],
            ]
        );

        // Rapikan instalasi lama yang sempat cuma punya 1 loket bernama
        // "Fotografer" (nama lama sebelum ada Fotografer 1 & 2) —
        // gabungkan ke "Fotografer 1" biar gak ada loket duplikat/nyasar.
        Counter::where('location_id', $studio->id)
            ->where('name', 'Fotografer')
            ->delete();

        // ==========================
        // User Staff Studio (PP)
        // ==========================

        // CS & Kasir sekarang "Pilihan" (gantian, pilih lokasi & loket tiap
        // login lewat operator.choose-location) -- gak dikunci ke lokasi
        // tertentu lagi, biar bisa gampang pindah antara PP & BK.
        User::updateOrCreate(
            ['email' => 'cs1@papyrusphoto.com'],
            [
                'name' => 'CS & Kasir',
                'role' => 'operator',
                'location_id' => null,
                'preferred_counter_id' => null,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        // Fotografer dengan loket tetap (Studio 1/2/3) -- location_id
        // otomatis ngikut lokasi loket tetapnya, sama kayak yang dilakukan
        // AdminController::storeUser()/updateUser() waktu ini diisi lewat
        // form Kelola Pengguna.
        User::updateOrCreate(
            ['email' => 'fotografer1@papyrusphoto.com'],
            [
                'name' => 'Fotografer1',
                'role' => 'fotografer',
                'location_id' => $counterFotografer1->location_id,
                'preferred_counter_id' => $counterFotografer1->id,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'fotografer2@papyrusphoto.com'],
            [
                'name' => 'Fotografer2',
                'role' => 'fotografer',
                'location_id' => $counterFotografer2->location_id,
                'preferred_counter_id' => $counterFotografer2->id,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'fotografer3@papyrusphoto.com'],
            [
                'name' => 'Fotografer3',
                'role' => 'fotografer',
                'location_id' => $counterFotografer3->location_id,
                'preferred_counter_id' => $counterFotografer3->id,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        // Fotografer "Pilihan" (gantian) -- gak dikunci ke loket/studio
        // tertentu, milih sendiri tiap login lewat operator.choose-location,
        // dan bisa pindah kapan saja lewat tombol "Ganti Lokasi".
        foreach (['Ivy' => 'ivy', 'Tufik' => 'tufik', 'Hasan' => 'hasan', 'Abe' => 'abe'] as $name => $slug) {
            User::updateOrCreate(
                ['email' => "{$slug}@papyrusphoto.com"],
                [
                    'name' => $name,
                    'role' => 'fotografer',
                    'location_id' => null,
                    'preferred_counter_id' => null,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}