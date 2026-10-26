<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Display - {{ $location->name }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 text-white min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-slate-800 px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ $location->name }}</h1>
                    <p class="text-slate-400">{{ $location->description }}</p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold" id="clock">--:--:--</div>
                    <div class="text-slate-400" id="date">--</div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="grid lg:grid-cols-3 gap-8 h-full">
                <!-- Currently Calling -->
                <div class="lg:col-span-2">
                    <h2 class="text-xl font-semibold text-slate-300 mb-4">SEDANG DIPANGGIL</h2>
                    @php
                        $calling = $location->tickets()->whereIn('status', ['calling', 'serving'])->with('counter')->get();
                        $waiting = $location->waitingTickets()->take(10)->get();
                    @endphp
                    <div id="calling-tickets" class="grid md:grid-cols-2 gap-6">
                        @if($calling->isEmpty())
                            <div class="bg-slate-700 rounded-2xl p-8 text-center col-span-2">
                                <p class="text-slate-400 text-xl">Tidak ada yang dipanggil</p>
                            </div>
                        @else
                            @foreach($calling as $ticket)
                                <div class="bg-green-600 rounded-2xl p-8 text-center">
                                    <p class="text-green-200 mb-2">{{ $ticket->counter?->name }}</p>
                                    <p class="text-6xl font-bold">{{ $ticket->ticket_number }}</p>
                                    @if($ticket->customer_name)
                                        <p class="text-green-200 mt-2">{{ $ticket->customer_name }}</p>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Waiting Queue -->
                <div class="bg-slate-800 rounded-2xl p-6">
                    <h2 class="text-xl font-semibold text-slate-300 mb-4">ANTRIAN BERIKUTNYA</h2>
                    <div id="waiting-tickets" class="space-y-3">
                        @if($waiting->isEmpty())
                            <p class="text-slate-400 text-center py-4">Tidak ada antrian</p>
                        @else
                            @foreach($waiting as $ticket)
                                <div class="flex items-center justify-between py-3 border-b border-slate-700">
                                    <span class="text-xl font-semibold">{{ $ticket->ticket_number }}</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-slate-800 px-8 py-4">
            <div class="flex items-center justify-between text-slate-400">
                <span>{{ config('app.name') }} - Sistem Manajemen Antrian</span>
                <span id="waiting-count">{{ $waiting->count() }} antrian menunggu</span>
            </div>
        </footer>
    </div>

    <script type="module">
        // Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('id-ID');
            document.getElementById('date').textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Auto-refresh nyata via AJAX: ambil ulang halaman ini, lalu tukar
        // hanya fragment yang berubah — tidak ada reload/kedip layar penuh,
        // dan tidak butuh aksi manual apa pun.
        let pollTimer;

        async function refreshDisplay() {
            clearTimeout(pollTimer);
            pollTimer = setTimeout(async () => {
                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                    });
                    if (!response.ok) return;
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    ['calling-tickets', 'waiting-tickets', 'waiting-count'].forEach(id => {
                        const newEl = doc.getElementById(id);
                        const oldEl = document.getElementById(id);
                        if (newEl && oldEl) {
                            oldEl.innerHTML = newEl.innerHTML;
                        }
                    });
                } catch (err) {
                    console.warn('Realtime refresh failed:', err);
                }
            }, 300);
        }

        setInterval(refreshDisplay, 8000);

        // WebSocket listeners. window.Echo bisa jadi belum tersedia jika
        // broadcasting (Laravel Reverb) belum dikonfigurasi/dijalankan —
        // jangan sampai itu membuat halaman ini error. Polling berkala di
        // atas tetap menjaga tampilan ini otomatis ter-update.
        if (window.Echo) {
            window.Echo.channel('location.{{ $location->id }}')
                .listen('.ticket.called', (e) => {
                    const audio = new Audio('/sounds/ding.mp3');
                    audio.play().catch(() => {});
                    refreshDisplay();
                })
                .listen('.ticket.status.changed', (e) => {
                    refreshDisplay();
                })
                .listen('.queue.updated', (e) => {
                    refreshDisplay();
                });
        } else {
            console.warn('Realtime (Echo) belum aktif; menggunakan auto-refresh berkala.');
        }
    </script>
</body>
</html>
