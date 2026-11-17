<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Hari wisuda sebuah kampus -- ditandai di kalender List Booking (hari ramai booking foto wisuda). */
class GraduationDay extends Model
{
    protected $fillable = ['date', 'campus', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /** Saran nama kampus di Yogyakarta (cuma isi dropdown, tanggalnya diisi sendiri oleh CS). */
    public const CAMPUS_SUGGESTIONS = [
        'UGM', 'UNY', 'UIN Sunan Kalijaga', 'UPN Veteran Yogyakarta', 'UII', 'UAD', 'UMY',
        'Universitas Sanata Dharma', 'UKDW', 'Universitas Atma Jaya Yogyakarta', 'ISI Yogyakarta',
        'Universitas Amikom Yogyakarta', 'Universitas Widya Mataram', 'Universitas Teknologi Yogyakarta',
        'Poltekkes Kemenkes Yogyakarta', 'STPN', 'Politeknik/Sekolah Vokasi lainnya',
    ];
}
