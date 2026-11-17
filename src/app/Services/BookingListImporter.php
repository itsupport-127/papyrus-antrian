<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPackage;
use App\Models\Location;
use App\Support\BookingListParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Import file Excel "LIST BOOKING" ke tabel bookings (source = 'excel').
 * Baris yang sudah ada (lokasi + tanggal + jam + kode order/nama sama) DILEWATI, jadi aman
 * kalau file yang sama diimpor dua kali, dan tidak pernah menimpa reservasi dari web/CS.
 */
class BookingListImporter
{
    public function __construct(private BookingListExcel $excel)
    {
    }

    /**
     * @return array{created: int, duplicates: int, review: int, skipped: list<array<string, mixed>>, sheets: array<string, int>}
     */
    public function import(Location $location, string $path): array
    {
        $sheets = $this->excel->readSheets($path);

        $known = [];
        foreach (BookingPackage::pluck('name') as $name) {
            $known[BookingPackage::normalizeName($name)] = $name;
        }

        $parsed = [];
        $skipped = [];
        $perSheet = [];
        foreach ($sheets as $title => $rows) {
            $res = BookingListParser::parseSheet($title, $rows, (int) now()->format('Y'), $known, now());
            if (!$res['header_found'] && $rows === []) {
                continue; // sheet kosong
            }
            foreach ($res['rows'] as $r) {
                $parsed[] = $r;
            }
            foreach ($res['skipped'] as $s) {
                $skipped[] = $s;
            }
            $perSheet[$title] = count($res['rows']);
        }

        if ($parsed === []) {
            return ['created' => 0, 'duplicates' => 0, 'review' => 0, 'skipped' => $skipped, 'sheets' => $perSheet];
        }

        $dates = array_column($parsed, 'booking_date');
        $existing = [];
        Booking::where('location_id', $location->id)
            ->whereBetween('booking_date', [min($dates), max($dates)])
            ->get(['order_code', 'customer_name', 'booking_date', 'booking_time'])
            ->each(function (Booking $b) use (&$existing) {
                $existing[$this->key($b->order_code, $b->customer_name, $b->booking_date->format('Y-m-d'), $b->booking_time->format('H:i'))] = true;
            });

        $created = 0;
        $duplicates = 0;
        $review = 0;

        DB::transaction(function () use ($parsed, $location, &$existing, &$created, &$duplicates, &$review) {
            foreach ($parsed as $r) {
                $key = $this->key($r['order_code'], $r['customer_name'], $r['booking_date'], $r['booking_time']);
                if (isset($existing[$key])) {
                    $duplicates++;
                    continue;
                }
                $existing[$key] = true;

                Booking::create([
                    'location_id' => $location->id,
                    'customer_name' => $r['customer_name'],
                    'customer_phone' => null,
                    'order_code' => $r['order_code'],
                    'package_name' => $r['package_name'],
                    'people_count' => $r['people_count'],
                    'notes' => $r['notes'],
                    'booking_date' => $r['booking_date'],
                    'booking_time' => $r['booking_time'],
                    'status' => $r['status'],
                    'booking_code' => Booking::generateBookingCode($location, Carbon::parse($r['booking_date'])),
                    'source' => 'excel',
                    'univ' => $r['univ'],
                    'arrived_time' => $r['arrived_time'],
                    'started_time' => $r['started_time'],
                    'finished_time' => $r['finished_time'],
                    'needs_review' => $r['needs_review'],
                    'review_note' => $r['review_note'],
                ]);
                $created++;
                if ($r['needs_review']) {
                    $review++;
                }
            }
        });

        return ['created' => $created, 'duplicates' => $duplicates, 'review' => $review, 'skipped' => $skipped, 'sheets' => $perSheet];
    }

    private function key(?string $code, ?string $name, string $date, string $time): string
    {
        return ($code ?: mb_strtoupper(trim((string) $name))) . '|' . $date . '|' . substr($time, 0, 5);
    }
}
