<x-layout :title="'QR Order - ' . $location->name">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 py-10 print:bg-white print:py-0">
        <div class="max-w-md mx-auto px-4">
            <div id="print-area" class="bg-white rounded-3xl shadow-xl border border-slate-200 p-10 text-center print:shadow-none print:border-0">
                <p class="text-sm font-semibold tracking-widest text-slate-400 uppercase mb-1">{{ $location->name }}</p>
                <h1 class="text-2xl font-black text-slate-900 mb-6">Scan untuk Ambil Antrian</h1>

                <div id="qrcode-large" class="inline-block p-4 bg-white border-4 border-slate-900 rounded-2xl"></div>

                <p class="text-slate-500 mt-6">
                    Arahkan kamera HP Anda ke QR code ini, isi data singkat, lalu Anda akan mendapatkan
                    nomor tiket dan link untuk memantau status pesanan.
                </p>

                <p class="text-xs text-slate-400 mt-6 break-all">{{ route('tickets.create', $location) }}</p>
            </div>

            <div class="mt-6 text-center print:hidden">
                <button onclick="window.print()" class="inline-flex items-center bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-3 rounded-xl font-semibold">
                    Cetak QR
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        if (document.getElementById('qrcode-large') && typeof QRCode !== 'undefined') {
            new QRCode(document.getElementById('qrcode-large'), {
                text: '{{ route('tickets.create', $location) }}',
                width: 260,
                height: 260,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
            });
        }
    </script>
    @endpush
</x-layout>
