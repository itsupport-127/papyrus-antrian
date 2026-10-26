@php
    $operatorTab = fn (bool $active) => $active
        ? 'text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-3 py-1.5 text-sm font-semibold rounded-lg whitespace-nowrap shrink-0'
        : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-3 py-1.5 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50 whitespace-nowrap shrink-0';
@endphp
<div class="flex items-center gap-1 overflow-x-auto pb-3 -mt-1">
    <a href="{{ route('operator.dashboard') }}" class="{{ $operatorTab(request()->routeIs('operator.dashboard') || request()->routeIs('operator.choose-location') || request()->routeIs('operator.choose-counter') || request()->routeIs('operator.tickets.*') || request()->routeIs('operator.bookings.*')) }}">
        Dashboard
    </a>
    <a href="{{ route('operator.booking-slots') }}" class="{{ $operatorTab(request()->routeIs('operator.booking-slots*')) }}">
        Jadwal Reservasi
    </a>
    {{-- "Master Data" -- database pelanggan lengkap (SEMUA histori tiket
         lintas hari & lintas lokasi berdasarkan no. telepon), BEDA dari tab
         "Data Pelanggan" di dalam Dashboard (cuma tiket HARI INI). Route
         admin.customers tadinya admin-only, sekarang dibuka juga buat CS &
         Kasir (lihat routes/web.php). --}}
    <a href="{{ route('admin.customers') }}" class="{{ $operatorTab(request()->routeIs('admin.customers*')) }}">
        Master Data Pelanggan
    </a>
</div>
