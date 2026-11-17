<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Baca / tulis file .xlsx "LIST BOOKING". Butuh paket: composer require phpoffice/phpspreadsheet
 * Logika perapihan data ada di App\Support\BookingListParser (tanpa dependensi, mudah diuji).
 */
class BookingListExcel
{
    private const MONTH_NAMES = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 5 => 'MEI', 6 => 'JUNI',
        7 => 'JULI', 8 => 'AGUSTUS', 9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER',
    ];

    /**
     * Semua sheet -> baris sel mentah (null | int | float | string | DateTimeImmutable).
     *
     * @return array<string, array<int, array<int, mixed>>>
     */
    public function readSheets(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $spreadsheet = $reader->load($path);
        $result = [];

        foreach ($spreadsheet->getWorksheetIterator() as $ws) {
            $rows = [];
            foreach ($ws->getRowIterator() as $row) {
                $cells = [];
                $iter = $row->getCellIterator();
                $iter->setIterateOnlyExistingCells(true);
                foreach ($iter as $cell) {
                    $col = Coordinate::columnIndexFromString($cell->getColumn()) - 1;
                    if ($col > 30) {
                        continue;
                    }
                    try {
                        $v = $cell->isFormula() ? $cell->getCalculatedValue() : $cell->getValue();
                    } catch (\Throwable) {
                        $v = null;
                    }
                    if (is_object($v)) {
                        $v = (string) $v; // rich text
                    }
                    if ((is_int($v) || is_float($v)) && XlsDate::isDateTime($cell)) {
                        $v = \DateTimeImmutable::createFromMutable(XlsDate::excelToDateTimeObject($v));
                    }
                    $cells[$col] = $v;
                }
                $rows[$row->getRowIndex() - 1] = $cells;
            }
            // Pastikan indeks berurutan dari 0 (baris kosong jadi array kosong).
            $max = $rows ? max(array_keys($rows)) : -1;
            $filled = [];
            for ($i = 0; $i <= $max; $i++) {
                $filled[$i] = $rows[$i] ?? [];
            }
            $result[$ws->getTitle()] = $filled;
        }

        $spreadsheet->disconnectWorksheets();

        return $result;
    }

    /**
     * Export booking ke .xlsx dengan tampilan SAMA seperti Excel "LIST BOOKING" studio: satu sheet per
     * bulan ("11. NOVEMBER 2026"), judul merah muda (Lora 27, digabung 2 baris), kolom
     * UNIV | TANGGAL BOOKING | JAM BOOKING | KO | PAKET | JUMLAH ORANG | KETERANGAN -- ditambah
     * DATANG | MULAI | SELESAI sebelum KETERANGAN hanya di bulan yang memang punya data jam itu (seperti
     * sheet Februari/April di file asli). Tanggal hanya di baris pertama tiap hari, warna abu-abu
     * selang-seling per hari, baris batal merah muda & "reschedule" oranye muda, tanggal wisuda ungu.
     * Hasil export bisa diedit lalu di-import lagi.
     *
     * @param  Collection<int, Booking>  $bookings
     * @param  Collection<string, mixed>|null  $graduationDays  hasil groupBy 'Y-m-d' dari GraduationDay
     */
    public function download(Collection $bookings, string $filename, ?Collection $graduationDays = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $graduationDays ??= collect();

        $groups = $bookings->sortBy(fn (Booking $b) => $b->booking_date->format('Y-m-d') . ' ' . $b->booking_time->format('H:i'))
            ->groupBy(fn (Booking $b) => $b->booking_date->format('Y-m'));

        if ($groups->isEmpty()) {
            $groups = collect(['' => collect()]);
        }

        $labels = ['univ' => 'UNIV', 'date' => 'TANGGAL BOOKING', 'time' => 'JAM BOOKING', 'ko' => 'KO', 'package' => 'PAKET', 'people' => 'JUMLAH ORANG',
            'arrived' => 'DATANG', 'started' => 'MULAI', 'finished' => 'SELESAI', 'notes' => 'KETERANGAN'];
        $widths = ['univ' => 10, 'date' => 18.2, 'time' => 9.5, 'ko' => 32.8, 'package' => 18.6, 'people' => 15.5, 'arrived' => 12, 'started' => 12, 'finished' => 12];
        $border = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];

        $fill = function ($ws, string $range, string $rgb): void {
            $ws->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
        };
        // "H:i" -> pecahan hari (sel berformat jam), null kalau kosong/salah.
        $timeFraction = function (?string $t): ?float {
            if ($t && preg_match('/^(\\d{1,2}):(\\d{2})/', $t, $m)) {
                return (((int) $m[1]) * 60 + (int) $m[2]) / 1440;
            }

            return null;
        };
        $ticketTimes = function (Booking $b): array {
            $t = $b->ticket;

            return [
                $b->arrived_time ? substr($b->arrived_time, 0, 5) : $t?->created_at?->format('H:i'),
                $b->started_time ? substr($b->started_time, 0, 5) : ($t?->photo_served_at ?? $t?->served_at)?->format('H:i'),
                $b->finished_time ? substr($b->finished_time, 0, 5) : ($t?->photo_completed_at ?? $t?->completed_at)?->format('H:i'),
            ];
        };

        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        foreach ($groups as $ym => $items) {
            [$y, $m] = $ym !== '' ? array_map('intval', explode('-', $ym)) : [(int) date('Y'), (int) date('n')];

            // Kolom jam datang/mulai/selesai hanya muncul kalau bulan ini punya datanya.
            $hasTimes = $items->contains(fn (Booking $b) => count(array_filter($ticketTimes($b))) > 0);
            $cols = array_merge(['univ', 'date', 'time', 'ko', 'package', 'people'], $hasTimes ? ['arrived', 'started', 'finished'] : [], ['notes']);
            $colIdx = array_flip($cols); // key -> indeks 0..
            $letter = fn (string $key): string => Coordinate::stringFromColumnIndex($colIdx[$key] + 1);
            $last = Coordinate::stringFromColumnIndex(count($cols));
            $widths['notes'] = $hasTimes ? 67 : 45.9;

            $ws = $spreadsheet->createSheet();
            $ws->setTitle(sprintf('%02d. %s %d', $m, self::MONTH_NAMES[$m], $y));

            // Judul
            $ws->setCellValue('A1', 'LIST BOOKING');
            $ws->mergeCells("A1:{$last}2");
            $ws->getStyle("A1:{$last}2")->applyFromArray($border);
            $ws->getStyle('A1')->getFont()->setName('Lora')->setSize(27);
            $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $fill($ws, "A1:{$last}2", 'EAD1DC');

            // Judul kolom
            foreach ($cols as $i => $key) {
                $ws->setCellValue([$i + 1, 3], $labels[$key]);
            }
            $ws->getStyle("A3:{$last}3")->applyFromArray($border);
            $ws->getStyle("A3:{$last}3")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $fill($ws, "A3:{$last}3", 'D5A6BD');

            $r = 4;
            $prevDate = null;
            $dayIndex = -1;
            foreach ($items as $b) {
                $dateKey = $b->booking_date->format('Y-m-d');
                $newDay = $dateKey !== $prevDate;
                if ($newDay) {
                    $dayIndex++;
                    $prevDate = $dateKey;
                }

                [$arrived, $started, $finished] = $ticketTimes($b);
                $notes = (string) $b->notes;
                if ($b->status === 'cancelled' && !preg_match('/cancel|batal/i', $notes)) {
                    $notes = trim('CANCEL ' . $notes);
                }

                $ws->setCellValue([$colIdx['univ'] + 1, $r], $b->univ);
                if ($newDay) { // tanggal hanya di baris pertama tiap hari, seperti Excel asli
                    $ws->setCellValue([$colIdx['date'] + 1, $r], XlsDate::PHPToExcel($b->booking_date->copy()->startOfDay()));
                    $ws->getStyle($letter('date') . $r)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
                    if ($graduationDays->has($dateKey)) {
                        $fill($ws, $letter('date') . $r, 'D9D2E9');
                    }
                }

                $timeCells = ['time' => substr($b->booking_time->format('H:i'), 0, 5)];
                if ($hasTimes) {
                    $timeCells += ['arrived' => $arrived, 'started' => $started, 'finished' => $finished];
                }
                foreach ($timeCells as $key => $val) {
                    $frac = $timeFraction($val);
                    if ($frac !== null && !($key === 'time' && $val === '00:00')) {
                        $ws->setCellValue([$colIdx[$key] + 1, $r], $frac);
                        $ws->getStyle($letter($key) . $r)->getNumberFormat()->setFormatCode('h:mm');
                    }
                }

                $ws->setCellValue([$colIdx['ko'] + 1, $r], trim(($b->order_code ? $b->order_code . '  ' : '') . $b->customer_name));
                $ws->setCellValue([$colIdx['package'] + 1, $r], $b->package_name);
                $ws->setCellValue([$colIdx['people'] + 1, $r], $b->people_count);
                $ws->setCellValue([$colIdx['notes'] + 1, $r], $notes !== '' ? $notes : null);

                $ws->getStyle("A{$r}:{$last}{$r}")->applyFromArray($border);
                $ws->getStyle("A{$r}:{$last}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $ws->getStyle($letter('ko') . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $ws->getStyle($letter('notes') . $r)->getAlignment()->setWrapText(true);

                // Warna: abu-abu selang-seling per hari; batal merah muda; reschedule oranye muda.
                $rowColor = $dayIndex % 2 === 0 ? 'CCCCCC' : 'B7B7B7';
                if ($b->status === 'cancelled') {
                    $rowColor = 'F4CCCC';
                    $fill($ws, $letter('notes') . $r, 'F4CCCC');
                } elseif (preg_match('/resc?hedule/i', $notes)) {
                    $rowColor = 'FCE5CD';
                }
                $fill($ws, $letter('time') . $r . ':' . $letter('people') . $r, $rowColor);
                $r++;
            }

            foreach ($cols as $key) {
                $ws->getColumnDimension($letter($key))->setWidth($widths[$key]);
            }
            $ws->freezePane('A4');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            // Peringatan/notice PHP yang ikut tercetak ke output akan merusak isi file .xlsx
            // (zip), jadi dimatikan & buffer dibersihkan sebelum file ditulis.
            @ini_set('display_errors', '0');
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
