<?php

/**
 * Pengaturan reservasi.
 *
 * Daftar "Pilihan Paket" TIDAK lagi di sini -- sekarang dikelola Admin lewat
 * menu "Paket Reservasi" (tabel booking_packages), jadi menambah/mengubah paket
 * tidak perlu edit file atau clear-cache.
 */
return [
    // Batas atas input (jaga-jaga salah ketik)
    'max_people' => 500,
    'max_costume_changes' => 20,
    'max_dp_amount' => 100000000,
];
