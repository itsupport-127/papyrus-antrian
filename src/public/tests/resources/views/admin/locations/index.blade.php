<x-layout title="Kelola Lokasi">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Kelola Lokasi" subtitle="Tambah, edit, atau hapus lokasi layanan dan loket" />

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8">
            <div class="flex items-center justify-between mb-8">
                <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white text-sm inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Dashboard
                </a>
                <a href="{{ route('admin.locations.create') }}" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-2 rounded-lg font-medium">
                    Tambah Lokasi
                </a>
            </div>

        <div class="bg-white rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Lokasi</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Kode</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Layanan</th>
                            <th class="text-center px-6 py-3 text-xs font-medium text-slate-500 uppercase">Loket</th>
                            <th class="text-center px-6 py-3 text-xs font-medium text-slate-500 uppercase">Operator</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Jam Operasional</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Status</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Reservasi</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="locations-table-body" class="divide-y divide-slate-200">
                        @forelse($locations as $location)
                            <tr>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-900">{{ $location->name }}</span>
                                    @if($location->description)
                                        <span class="block text-sm text-slate-500">{{ Str::limit($location->description, 50) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-sm">{{ $location->code }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $serviceLabels = ['both' => 'Tiket & Reservasi', 'ticket' => 'Tiket saja', 'booking' => 'Reservasi saja'];
                                    @endphp
                                    <span class="text-sm text-slate-600">{{ $serviceLabels[$location->service_type] ?? $location->service_type }}</span>
                                </td>
                                <td class="px-6 py-4 text-center text-slate-600">{{ $location->counters_count }}</td>
                                <td class="px-6 py-4 text-center text-slate-600">{{ $location->users_count }}</td>
                                <td class="px-6 py-4 text-slate-600 text-sm">
                                    {{ \Carbon\Carbon::parse($location->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($location->close_time)->format('H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        @if($location->is_active)
                                            <span class="inline-flex items-center text-green-600 text-sm">
                                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-slate-500 text-sm">
                                                <span class="w-2 h-2 bg-slate-400 rounded-full mr-1.5"></span>
                                                Nonaktif
                                            </span>
                                        @endif
                                        <span class="inline-flex items-center text-xs {{ $location->isOpen() ? 'text-green-600' : 'text-red-500' }}">
                                            {{ $location->isOpen() ? 'Sedang buka sekarang' : 'Sedang tutup sekarang' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($location->offersBooking())
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-block w-fit text-xs font-semibold px-2 py-0.5 rounded-full {{ $location->isFreeBookingMode() ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                                {{ $location->isFreeBookingMode() ? 'Bebas Pilih Jam' : 'Jadwal Ditentukan' }}
                                            </span>
                                            @if(!$location->isFreeBookingMode())
                                                @if($location->active_booking_slots_count > 0)
                                                    <span class="text-xs text-slate-500">{{ $location->active_booking_slots_count }} slot aktif</span>
                                                @else
                                                    <span class="text-xs text-red-500 font-medium">⚠ 0 slot — reservasi akan gagal</span>
                                                @endif
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400">Tidak menerima reservasi</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <a href="{{ route('admin.locations.edit', $location) }}" class="text-blue-600 hover:text-blue-700 text-sm">Edit</a>
                                        <a href="{{ route('admin.counters', $location) }}" class="text-slate-600 hover:text-slate-700 text-sm">Loket</a>
                                        @if($location->offersBooking())
                                            <a href="{{ route('admin.booking-slots', $location) }}" class="text-slate-600 hover:text-slate-700 text-sm">Jadwal Reservasi</a>
                                        @endif
                                        <form method="POST" action="{{ route('admin.locations.delete', $location) }}" onsubmit="return confirm('Yakin ingin menghapus lokasi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-500">
                                    Belum ada lokasi. <a href="{{ route('admin.locations.create') }}" class="text-blue-600 hover:text-blue-700">Tambah lokasi baru</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.autoRefreshIds(['locations-table-body'], 6000);
    </script>
    @endpush
</x-layout>