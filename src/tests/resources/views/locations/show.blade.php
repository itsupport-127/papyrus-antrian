<x-layout :title="$location->name">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="{{ route('locations.index') }}" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ $location->name }}</h1>
                <p class="text-slate-600 dark:text-slate-400">{{ $location->description }}</p>
            </div>
            @if($location->offersWalkInTicket())
                <a href="{{ route('tickets.create', $location) }}" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-3 rounded-lg font-medium">
                    Ambil Tiket
                </a>
            @elseif($location->offersBooking())
                <a href="{{ route('bookings.create', $location) }}" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-3 rounded-lg font-medium">
                    Reservasi
                </a>
            @endif
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Currently Serving -->
            <div class="lg:col-span-2">
                <div id="currently-serving-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Sedang Dilayani</h2>

                    @if($currentlyServing->isEmpty())
                        <p class="text-slate-500 dark:text-slate-400 text-center py-8">Belum ada yang dilayani</p>
                    @else
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach($currentlyServing as $ticket)
                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $ticket->ticket_number }}</span>
                                        <span class="text-sm text-green-600 dark:text-green-400">{{ $ticket->counter?->name }}</span>
                                    </div>
                                    @if($ticket->customer_name)
                                        <p class="text-green-600 dark:text-green-400 text-sm mt-1">{{ $ticket->customer_name }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Waiting Queue -->
                <div id="waiting-queue-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 mt-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                        Antrian Menunggu
                        <span class="text-sm font-normal text-slate-500 dark:text-slate-400">({{ $waitingTickets->count() }} orang)</span>
                    </h2>

                    @if($waitingTickets->isEmpty())
                        <p class="text-slate-500 dark:text-slate-400 text-center py-8">Tidak ada antrian</p>
                    @else
                        <div class="space-y-3">
                            @foreach($waitingTickets as $index => $ticket)
                                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-700' : '' }}">
                                    <div class="flex items-center space-x-4">
                                        <span class="w-8 h-8 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-sm font-medium text-slate-600 dark:text-slate-300">
                                            {{ $index + 1 }}
                                        </span>
                                        <div>
                                            <span class="font-semibold text-slate-900 dark:text-white">{{ $ticket->ticket_number }}</span>
                                            @if($ticket->customer_name)
                                                <span class="text-slate-500 dark:text-slate-400 text-sm ml-2">{{ $ticket->customer_name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div id="sidebar-panel" class="space-y-6">
                <!-- Location Info -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Informasi Lokasi</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Kode</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">{{ $location->code }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Jam Operasional</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($location->open_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($location->close_time)->format('H:i') }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Loket Aktif</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">{{ $location->counters->count() }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Counters -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Loket</h3>
                    <div class="space-y-3">
                        @foreach($location->counters as $counter)
                            <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-slate-100 dark:border-slate-700' : '' }}">
                                <span class="font-medium text-slate-900 dark:text-white">{{ $counter->name }}</span>
                                @if($counter->currentOperator)
                                    <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-1 rounded">Aktif</span>
                                @else
                                    <span class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-2 py-1 rounded">Tidak Aktif</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Display Link -->
                <a href="{{ route('locations.display', $location) }}" target="_blank" class="block bg-blue-900 hover:bg-blue-800 dark:bg-blue-950 dark:hover:bg-blue-900 text-white text-center py-3 rounded-lg font-medium">
                    Buka Tampilan Display
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        let refreshTimer;

        async function refreshQueue() {
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(async () => {
                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                    });
                    if (!response.ok) return;
                    const html = await response.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');

                    ['currently-serving-panel', 'waiting-queue-panel', 'sidebar-panel'].forEach(id => {
                        const newEl = doc.getElementById(id);
                        const oldEl = document.getElementById(id);
                        if (newEl && oldEl) {
                            oldEl.innerHTML = newEl.innerHTML;
                        }
                    });
                } catch (err) {
                    console.warn('Realtime refresh failed:', err);
                }
            }, 400);
        }

        setInterval(refreshQueue, 5000);

        if (window.Echo) {
            window.Echo.channel('location.{{ $location->id }}')
                .listen('.ticket.created', refreshQueue)
                .listen('.ticket.called', refreshQueue)
                .listen('.ticket.status.changed', refreshQueue);
        }
    </script>
    @endpush
</x-layout>
