<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
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
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn()) - 1;
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
     * Export booking ke .xlsx, satu sheet per bulan ("11. NOVEMBER 2026"), format kolom sama dengan
     * Excel asli sehingga hasil export bisa diedit lalu di-import lagi.
     *
     * @param  Collection<int, Booking>  $bookings
     */
    public function download(Collection $bookings, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $groups = $bookings->sortBy(fn (Booking $b) => $b->booking_date->format('Y-m-d') . ' ' . $b->booking_time->format('H:i'))
            ->groupBy(fn (Booking $b) => $b->booking_date->format('Y-m'));

        if ($groups->isEmpty()) {
            $groups = collect(['' => collect()]);
        }

        $headers = ['UNIV', 'TANGGAL BOOKING', 'JAM BOOKING', 'KO', 'PAKET', 'JUMLAH ORANG', 'DATANG', 'MULAI', 'SELESAI', 'KETERANGAN', 'STATUS'];
        $statusLabels = ['pending' => 'Menunggu', 'confirmed' => 'Terjadwal', 'checked_in' => 'Check-in', 'completed' => 'Selesai', 'cancelled' => 'Batal'];

        foreach ($groups as $ym => $items) {
            [$y, $m] = $ym !== '' ? array_map('intval', explode('-', $ym)) : [(int) date('Y'), (int) date('n')];
            $title = sprintf('%02d. %s %d', $m, self::MONTH_NAMES[$m], $y);
            $ws = $spreadsheet->createSheet();
            $ws->setTitle($title);
            $ws->setCellValue('A1', 'LIST BOOKING');
            $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            foreach ($headers as $i => $h) {
                $ws->setCellValue([$i + 1, 3], $h);
            }
            $ws->getStyle('A3:K3')->getFont()->setBold(true);
            $ws->getStyle('A3:K3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9E3F5');

            $r = 4;
            foreach ($items as $b) {
                $t = $b->ticket;
                $arrived = $b->arrived_time ?? $t?->created_at?->format('H:i');
                $started = $b->started_time ?? ($t?->photo_served_at ?? $t?->served_at)?->format('H:i');
                $finished = $b->finished_time ?? ($t?->photo_completed_at ?? $t?->completed_at)?->format('H:i');
                $notes = (string) $b->notes;
                if ($b->status === 'cancelled' && !preg_match('/cancel|batal/i', $notes)) {
                    $notes = trim('CANCEL ' . $notes);
                }

                $ws->setCellValue([1, $r], $b->univ);
                $ws->setCellValue([2, $r], XlsDate::PHPToExcel($b->booking_date->copy()->startOfDay()));
                $ws->getStyle([2, $r])->getNumberFormat()->setFormatCode('dd-mm-yyyy');
                $ws->setCellValueExplicit([3, $r], $b->booking_time->format('H:i'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $ws->setCellValue([4, $r], trim(($b->order_code ? $b->order_code . ' ' : '') . $b->customer_name));
                $ws->setCellValue([5, $r], $b->package_name);
                $ws->setCellValue([6, $r], $b->people_count);
                foreach ([7 => $arrived, 8 => $started, 9 => $finished] as $col => $val) {
                    if ($val) {
                        $ws->setCellValueExplicit([$col, $r], $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }
                $ws->setCellValue([10, $r], $notes !== '' ? $notes : null);
                $ws->setCellValue([11, $r], ($statusLabels[$b->status] ?? $b->status) . ($b->needs_review ? ' (perlu dicek)' : ''));
                $r++;
            }

            foreach (range('A', 'K') as $col) {
                $ws->getColumnDimension($col)->setAutoSize(true);
            }
            $ws->getColumnDimension('J')->setAutoSize(false)->setWidth(45);
            $ws->getStyle('J4:J' . max(4, $r))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $ws->freezePane('A4');
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
