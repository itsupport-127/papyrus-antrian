<?php

namespace App\Support;

use DateTimeInterface;

/**
 * Pembaca "LIST BOOKING" ala Excel studio -> baris booking yang sudah dirapikan.
 *
 * Kelas ini MURNI logika (tanpa Laravel / database / PhpSpreadsheet) supaya gampang diuji:
 * ia menerima baris-baris sel mentah (null | int | float | string | DateTimeInterface) dan
 * mengembalikan baris booking + daftar baris yang dilewati. Pembacaan file .xlsx-nya ada di
 * App\Services\BookingListExcel.
 *
 * Kerapian yang ditangani (semuanya ada di file Excel asli):
 *  - Tanggal tertukar hari/bulan ("2026-01-02" di sheet Februari = 1 Februari) -> dibetulkan
 *    memakai bulan di nama sheet. Tanggal teks ("03-05-0226") juga dibaca.
 *  - Tanggal hanya diisi di baris pertama tiap hari -> dibawa ke baris berikutnya.
 *  - Jam berupa angka desimal ("9.3" = 09:30, "14.15" = 14:15, "12" = 12:00).
 *  - "KO" = kode order + nama ("JG035474 NINO").
 *  - Jumlah orang berisi tanggal / rentang ("15-20") -> ditandai "perlu dicek".
 *  - "CANCEL" di keterangan -> status batal.
 *  - Baris catatan (bukan booking) dilewati.
 * Baris yang meragukan TIDAK ditebak diam-diam: ditandai needs_review + review_note.
 */
class BookingListParser
{
    private const MONTHS = [
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4, 'MEI' => 5, 'JUNI' => 6,
        'JULI' => 7, 'AGUSTUS' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
        'JANUARY' => 1, 'FEBRUARY' => 2, 'MARCH' => 3, 'MAY' => 5, 'JUNE' => 6, 'JULY' => 7,
        'AUGUST' => 8, 'OCTOBER' => 10, 'DECEMBER' => 12,
    ];

    private const PACKAGE_ALIASES = [
        'GRUP BIG' => 'GROUP BIG',
        'GRUP MINI' => 'GROUP MINI',
        'ART 1' => 'ARTISTIK 1',
        'ART 2' => 'ARTISTIK 2',
    ];

    public static function monthFromSheetName(string $name): ?int
    {
        if (preg_match('/^\s*(\d{1,2})\s*[.\-)]/', $name, $m)) {
            $n = (int) $m[1];
            if ($n >= 1 && $n <= 12) {
                return $n;
            }
        }
        $upper = mb_strtoupper($name);
        foreach (self::MONTHS as $label => $n) {
            if (str_contains($upper, $label)) {
                return $n;
            }
        }

        return null;
    }

    public static function yearFromSheetName(string $name): ?int
    {
        return preg_match('/\b(20\d{2})\b/', $name, $m) ? (int) $m[1] : null;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows            baris sheet, urut dari baris 1 (indeks 0)
     * @param  array<string, string>          $knownPackages   [NAMA_UPPER => nama resmi] dari Paket Reservasi
     * @return array{rows: list<array<string, mixed>>, skipped: list<array{sheet: string, row: int, reason: string}>, header_found: bool}
     */
    public static function parseSheet(
        string $sheetName,
        array $rows,
        ?int $defaultYear = null,
        array $knownPackages = [],
        ?DateTimeInterface $today = null
    ): array {
        $sheetMonth = self::monthFromSheetName($sheetName);
        $year = self::yearFromSheetName($sheetName) ?? $defaultYear ?? (int) date('Y');
        $todayStr = ($today ?? new \DateTimeImmutable('now'))->format('Y-m-d');

        [$headerIdx, $map] = self::findHeader($rows);
        if ($headerIdx === null) {
            return ['rows' => [], 'skipped' => [['sheet' => $sheetName, 'row' => 0, 'reason' => 'Baris judul kolom (KO, PAKET, ...) tidak ditemukan']], 'header_found' => false];
        }

        $out = [];
        $skipped = [];
        $seen = [];
        $currentDate = null;
        $dateBroken = false;
        $dateNote = null;

        for ($i = $headerIdx + 1, $n = count($rows); $i < $n; $i++) {
            $r = $rows[$i];
            $rowNo = $i + 1;
            $cell = fn (string $key) => isset($map[$key]) ? ($r[$map[$key]] ?? null) : null;

            // Tanggal: diisi hanya di baris pertama tiap hari -> dibawa ke bawah.
            $rawDate = $cell('date');
            if (!self::isBlank($rawDate)) {
                [$d, $note] = self::parseDate($rawDate, $sheetMonth, $year);
                $currentDate = $d;
                $dateNote = $note;
                $dateBroken = $d === null;
            }

            $ko = $cell('ko');
            if (self::isBlank($ko)) {
                if (self::rowHasContent($r, $map)) {
                    $skipped[] = ['sheet' => $sheetName, 'row' => $rowNo, 'reason' => 'Tanpa nama/kode order'];
                }
                continue;
            }

            $rawTime = $cell('time');
            $rawPackage = $cell('package');
            $rawPeople = $cell('people');

            [$code, $name] = self::splitKo($ko);

            // Baris catatan: ada teks di kolom KO tapi tanpa kode, jam, paket, maupun jumlah orang.
            if ($code === null && self::isBlank($rawTime) && self::isBlank($rawPackage) && self::isBlank($rawPeople)) {
                $skipped[] = ['sheet' => $sheetName, 'row' => $rowNo, 'reason' => 'Catatan, bukan booking: ' . mb_substr($name, 0, 60)];
                continue;
            }

            if ($currentDate === null) {
                $skipped[] = ['sheet' => $sheetName, 'row' => $rowNo, 'reason' => $dateBroken ? ('Tanggal tidak terbaca: ' . ($dateNote ?? '')) : 'Tanggal kosong'];
                continue;
            }

            $review = [];
            if ($dateNote) {
                $review[] = $dateNote;
            }

            [$time, $tNote] = self::parseTime($rawTime);
            if ($time === null) {
                $review[] = $tNote ?? 'Jam kosong';
                $time = '00:00';
            }

            [$people, $pNote] = self::parseCount($rawPeople);
            if ($pNote) {
                $review[] = $pNote;
            }

            $package = self::normalizePackage($rawPackage, $knownPackages);
            if ($package === null) {
                $review[] = 'Paket kosong';
            }

            if ($code === null) {
                $review[] = 'Kode order (JG...) tidak ada';
            }

            $notes = self::isBlank($cell('notes')) ? null : trim(preg_replace('/[ \t]+/', ' ', self::str($cell('notes'))));
            $isCancel = $notes !== null && preg_match('/\b(cancel|canceled|cancelled|batal)\b/i', $notes) === 1;

            $times = [];
            foreach (['arrived' => 'DATANG', 'started' => 'MULAI', 'finished' => 'SELESAI'] as $key => $label) {
                $raw = $cell($key);
                $times[$key] = null;
                if (self::isBlank($raw)) {
                    continue;
                }
                [$tv, ] = self::parseTime($raw);
                if ($tv !== null) {
                    $times[$key] = $tv;
                } else {
                    $review[] = "Kolom {$label} tidak terbaca (" . mb_substr(self::str($raw), 0, 30) . ')';
                }
            }

            if ($pNote !== null && $people === null && $rawPeople instanceof DateTimeInterface) {
                $notes = trim(($notes ? $notes . ' | ' : '') . 'Jumlah orang di Excel: ' . $rawPeople->format('n-j'));
            }

            $dupKey = ($code ?? mb_strtoupper($name)) . '|' . $currentDate . '|' . $time;
            if (isset($seen[$dupKey])) {
                $skipped[] = ['sheet' => $sheetName, 'row' => $rowNo, 'reason' => 'Ganda di file (kode + tanggal + jam sama): ' . $name];
                continue;
            }
            $seen[$dupKey] = true;

            $status = $isCancel ? 'cancelled' : ($currentDate < $todayStr ? 'completed' : 'confirmed');

            $univ = $cell('univ');

            $out[] = [
                'sheet' => $sheetName,
                'row_no' => $rowNo,
                'booking_date' => $currentDate,
                'booking_time' => $time,
                'order_code' => $code,
                'customer_name' => $name,
                'package_name' => $package,
                'people_count' => $people,
                'notes' => $notes,
                'status' => $status,
                'univ' => self::isBlank($univ) ? null : mb_substr(trim(self::str($univ)), 0, 100),
                'arrived_time' => $times['arrived'],
                'started_time' => $times['started'],
                'finished_time' => $times['finished'],
                'needs_review' => $review !== [],
                'review_note' => $review ? mb_substr(implode('; ', array_unique($review)), 0, 500) : null,
            ];
        }

        return ['rows' => $out, 'skipped' => $skipped, 'header_found' => true];
    }

    // ------------------------------------------------------------------ header

    /** @return array{0: ?int, 1: array<string, int>} */
    private static function findHeader(array $rows): array
    {
        foreach (array_slice($rows, 0, 10, true) as $idx => $r) {
            $norm = [];
            foreach ($r as $c => $v) {
                $norm[$c] = self::normHeader($v);
            }
            if (in_array('KO', $norm, true) && in_array('PAKET', $norm, true)) {
                $map = [];
                foreach ($norm as $c => $h) {
                    $key = match (true) {
                        $h === 'KO' => 'ko',
                        $h === 'UNIV' => 'univ',
                        $h === 'TANGGAL BOOKING' || $h === 'TANGGAL' => 'date',
                        $h === 'JAM BOOKING' || $h === 'JAM' => 'time',
                        $h === 'PAKET' => 'package',
                        $h === 'JUMLAH ORANG' => 'people',
                        $h === 'KETERANGAN' => 'notes',
                        str_starts_with($h, 'DATANG') => 'arrived',
                        str_starts_with($h, 'MULAI'), str_starts_with($h, 'MASUK'), str_starts_with($h, 'KO MASUK') => 'started',
                        str_starts_with($h, 'SELESAI'), str_starts_with($h, 'KELUAR') => 'finished',
                        default => null,
                    };
                    if ($key !== null && !isset($map[$key])) {
                        $map[$key] = $c;
                    }
                }

                return [$idx, $map];
            }
        }

        return [null, []];
    }

    private static function normHeader(mixed $v): string
    {
        if (!is_string($v)) {
            return '';
        }

        return trim(preg_replace('/\s*\/\s*/', '/', preg_replace('/\s+/', ' ', mb_strtoupper($v))));
    }

    // ------------------------------------------------------------------ nilai

    private static function isBlank(mixed $v): bool
    {
        return $v === null || (is_string($v) && trim($v) === '');
    }

    private static function str(mixed $v): string
    {
        if ($v instanceof DateTimeInterface) {
            return $v->format('Y-m-d');
        }
        if (is_float($v) && floor($v) == $v) {
            return (string) (int) $v;
        }

        return trim((string) $v);
    }

    private static function rowHasContent(array $r, array $map): bool
    {
        foreach (['time', 'package', 'people'] as $k) {
            if (isset($map[$k]) && !self::isBlank($r[$map[$k]] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{0: ?string, 1: string} [kode order, nama] */
    public static function splitKo(mixed $ko): array
    {
        $raw = trim(preg_replace('/\s+/u', ' ', self::str($ko)));
        if (preg_match('/^(JG\s*\d+)\s*(.*)$/iu', $raw, $m)) {
            $code = strtoupper(preg_replace('/\s+/', '', $m[1]));
            $name = trim($m[2]);

            return [$code, $name !== '' ? $name : $code];
        }

        return [null, $raw];
    }

    /** @return array{0: ?string, 1: ?string} [Y-m-d, catatan perlu dicek] */
    public static function parseDate(mixed $v, ?int $sheetMonth, int $year): array
    {
        if ($v instanceof DateTimeInterface) {
            $y = (int) $v->format('Y');
            $m = (int) $v->format('n');
            $d = (int) $v->format('j');

            if ($sheetMonth !== null && $m !== $sheetMonth) {
                if ($d === $sheetMonth) {
                    [$m, $d] = [$d, $m]; // hari & bulan tertukar -> dibalik
                } else {
                    return [checkdate($m, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null, 'Tanggal ' . $v->format('d-m-Y') . ' tidak cocok dengan bulan sheet'];
                }
            }

            return checkdate($m, $d, $y) ? [sprintf('%04d-%02d-%02d', $y, $m, $d), null] : [null, 'Tanggal tidak valid'];
        }

        if (is_int($v) || is_float($v)) {
            $d = (int) $v;
            if ($sheetMonth !== null && $d >= 1 && $d <= 31 && checkdate($sheetMonth, $d, $year)) {
                return [sprintf('%04d-%02d-%02d', $year, $sheetMonth, $d), null];
            }

            return [null, 'Tanggal berupa angka ' . $v];
        }

        $s = trim((string) $v);
        if (preg_match('/^(\d{1,2})\s*[-\/.]\s*(\d{1,2})(?:\s*[-\/.]\s*(\d{2,4}))?$/', $s, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $y = $year;
            $yearTypo = false;
            if (isset($m[3]) && $m[3] !== '') {
                $y = (int) $m[3];
                if ($y < 1000) {
                    $yearTypo = strlen($m[3]) > 2; // "0226" salah ketik -> 2026 (dua digit "26" wajar)
                    $y = 2000 + ($y % 100);
                }
            }
            if ($sheetMonth !== null && $b === $sheetMonth) {
                $day = $a;
                $month = $b;
            } elseif ($sheetMonth !== null && $a === $sheetMonth) {
                $day = $b; // bentuk "bulan-hari" (tertukar)
                $month = $a;
            } else {
                // Tidak ada bulan sheet, atau tanggal lengkap yang bukan bulan sheet (mis. "20-09-2026"
                // di sheet Juli): dibaca hari-bulan apa adanya, dan ditandai bila bukan bulan sheet.
                $day = $a;
                $month = $b;
                if ($sheetMonth !== null) {
                    $yearTypo = true;
                }
            }

            return checkdate($month, $day, $y)
                ? [sprintf('%04d-%02d-%02d', $y, $month, $day), $yearTypo ? 'Tanggal teks "' . $s . '" dibaca ' . sprintf('%02d-%02d-%04d', $day, $month, $y) : null]
                : [null, 'Tanggal teks tidak valid: ' . $s];
        }

        return [null, 'Tanggal tidak terbaca: ' . mb_substr($s, 0, 30)];
    }

    /** @return array{0: ?string, 1: ?string} ["H:i", catatan] */
    public static function parseTime(mixed $v): array
    {
        if (self::isBlank($v)) {
            return [null, null];
        }

        if ($v instanceof DateTimeInterface) {
            return [$v->format('H:i'), null];
        }

        if (is_int($v) || is_float($v)) {
            $f = (float) $v;
            if ($f <= 0) {
                return [null, null];
            }
            if ($f < 1) { // pecahan hari (sel berformat jam)
                $min = (int) round($f * 1440);

                return [sprintf('%02d:%02d', intdiv($min, 60) % 24, $min % 60), null];
            }

            return self::decimalHours(rtrim(rtrim(sprintf('%.2F', $f), '0'), '.'));
        }

        $s = trim((string) $v);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $s, $m)) {
            return ((int) $m[1] <= 23 && (int) $m[2] <= 59)
                ? [sprintf('%02d:%02d', (int) $m[1], (int) $m[2]), null]
                : [null, 'Jam tidak valid: ' . $s];
        }
        if (preg_match('/^\d{1,2}([.,]\d{1,2})?$/', $s)) {
            return self::decimalHours(str_replace(',', '.', $s));
        }

        return [null, 'Jam tidak terbaca: ' . mb_substr($s, 0, 30)];
    }

    /** "9.3" -> 09:30, "14.15" -> 14:15, "12" -> 12:00, "13.1" -> 13:10 */
    private static function decimalHours(string $s): array
    {
        $parts = explode('.', $s, 2);
        $h = (int) $parts[0];
        $m = 0;
        if (isset($parts[1]) && $parts[1] !== '') {
            $m = strlen($parts[1]) === 1 ? ((int) $parts[1]) * 10 : (int) $parts[1];
        }
        if ($h > 23 || $m > 59) {
            return [null, 'Jam tidak valid: ' . $s];
        }

        return [sprintf('%02d:%02d', $h, $m), null];
    }

    /** @return array{0: ?int, 1: ?string} */
    public static function parseCount(mixed $v): array
    {
        if (self::isBlank($v)) {
            return [null, null];
        }
        if ($v instanceof DateTimeInterface) {
            return [null, 'Jumlah orang terbaca sebagai tanggal (' . $v->format('n-j') . '), isi manual'];
        }
        if (is_int($v) || is_float($v)) {
            return [(int) round((float) $v), null];
        }
        preg_match_all('/\d+/', (string) $v, $m);
        $nums = array_map('intval', $m[0]);
        if (count($nums) === 1) {
            return [$nums[0], preg_match('/^\s*\d+\s*$/', (string) $v) ? null : 'Jumlah orang "' . trim((string) $v) . '" dibaca ' . $nums[0]];
        }
        if (count($nums) > 1) {
            return [max($nums), 'Jumlah orang rentang "' . trim((string) $v) . '", dipakai ' . max($nums)];
        }

        return [null, 'Jumlah orang tidak terbaca: ' . mb_substr(trim((string) $v), 0, 30)];
    }

    /** @param array<string, string> $known [NAMA_UPPER => nama resmi] */
    public static function normalizePackage(mixed $v, array $known = []): ?string
    {
        if (self::isBlank($v)) {
            return null;
        }
        $p = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', self::str($v))));
        $p = self::PACKAGE_ALIASES[$p] ?? $p;

        return $known[$p] ?? $p;
    }
}
