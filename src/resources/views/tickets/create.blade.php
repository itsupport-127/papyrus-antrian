<x-layout :title="'Ambil Antrian - ' . $location->name">
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 py-8">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden">
                <!-- Header -->
                <div class="papyrus-swirl-bg bg-gradient-to-br from-lavender to-dustyrose text-white p-6 text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold">{{ $location->name }}</h1>
                    <p class="text-white/85 mt-1">Isi data untuk mengambil nomor antrian</p>
                </div>

                <!-- Queue Info -->
                <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-5">
                        <div class="text-center">
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Antrian saat ini</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $location->waitingTickets()->count() }}</p>
                            <p class="text-slate-500 dark:text-slate-400 text-xs">orang menunggu</p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="p-6">
                    @if($gpsConfig)
                        <div id="gps-status-box" class="mb-5 rounded-xl border-2 px-4 py-3 text-sm border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 flex items-center gap-2">
                            <x-icon-badge icon="map-pin" color="blue" size="sm" /> Memeriksa lokasi Anda...
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tickets.store', $location) }}" id="ticket-form">
                        @csrf
                        <input type="hidden" name="latitude" id="input-latitude" value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="input-longitude" value="{{ old('longitude') }}">

                        <div class="space-y-5">
                            <div>
                                <label for="customer_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Nama
                                </label>
                                <input
                                    type="text"
                                    id="customer_name"
                                    name="customer_name"
                                    value="{{ old('customer_name') }}"
                                    required
                                    pattern="[A-Za-zÀ-ÿ\s.'\-]+"
                                    title="Nama cuma boleh huruf dan spasi"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="Masukkan nama Anda"
                                >
                                <p class="js-field-error hidden text-red-600 text-sm mt-2"></p>
                                @error('customer_name')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="customer_phone" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    No. Telepon
                                </label>
                                <input
                                    type="tel"
                                    id="customer_phone"
                                    name="customer_phone"
                                    value="{{ old('customer_phone') }}"
                                    required
                                    inputmode="numeric"
                                    maxlength="14"
                                    pattern="08[0-9]{8,12}"
                                    title="Diawali 08, lalu angka saja (contoh: 081234567890)"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="08xxxxxxxxxx"
                                >
                                <p class="js-field-error hidden text-red-600 text-sm mt-2"></p>
                                @error('customer_phone')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="customer_email" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    id="customer_email"
                                    name="customer_email"
                                    value="{{ old('customer_email') }}"
                                    required
                                    title="Contoh: nama@email.com"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400"
                                    placeholder="nama@email.com"
                                >
                                <p class="js-field-error hidden text-red-600 text-sm mt-2"></p>
                                @error('customer_email')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        @if($serviceCategories->isNotEmpty())
                            <div>
                                <label for="service_category_id" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                    Layanan
                                </label>
                                <select
                                    id="service_category_id"
                                    name="service_category_id"
                                    class="w-full px-4 py-3.5 border-2 border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                >
                                    <option value="">Pilih layanan (opsional)</option>
                                    @foreach($serviceCategories as $category)
                                        <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('service_category_id')
                                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <button
                            type="submit"
                            id="submit-ticket-btn"
                            @if($gpsConfig) disabled @endif
                            class="w-full mt-6 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-4 rounded-xl font-bold text-lg shadow-lg shadow-blue-200 transition-all hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Ambil Nomor Antrian
                        </button>
                    </form>

                    <p class="text-slate-500 dark:text-slate-400 text-sm text-center mt-5">
                        Setelah dapat nomor antrian, CS/Kasir kami akan memanggil Anda untuk membantu menentukan paket foto yang sesuai.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Validasi instan di browser -- biar pelanggan langsung tahu
            // salahnya di mana TANPA nunggu form ke-submit & halaman reload.
            // Validasi "beneran" (yang gak bisa diakalin lewat DevTools)
            // tetap ada di server (TicketController::store) -- ini cuma
            // buat pengalaman pengisian yang lebih enak.
            const form = document.getElementById('ticket-form');
            if (!form) return;

            const rules = {
                customer_name: {
                    empty: 'Nama wajib diisi.',
                    invalid: 'Nama cuma boleh berisi huruf dan spasi (gak boleh angka/simbol).',
                    test: (v) => /^[\p{L}\s.'\-]+$/u.test(v),
                },
                customer_phone: {
                    empty: 'No. Telepon wajib diisi.',
                    invalid: 'Harus diawali "08", lalu angka saja, contoh: 081234567890.',
                    test: (v) => /^08[0-9]{8,12}$/.test(v),
                },
                customer_email: {
                    empty: 'Email wajib diisi.',
                    invalid: 'Format email gak valid, contoh: nama@email.com.',
                    test: (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v),
                },
            };

            function fieldErrorEl(input) {
                return input.closest('div').querySelector('.js-field-error');
            }

            function showError(input, message) {
                input.classList.add('border-red-400', 'dark:border-red-500');
                input.classList.remove('border-slate-200', 'dark:border-slate-600');
                const el = fieldErrorEl(input);
                if (el) { el.textContent = message; el.classList.remove('hidden'); }
            }

            function clearError(input) {
                input.classList.remove('border-red-400', 'dark:border-red-500');
                input.classList.add('border-slate-200', 'dark:border-slate-600');
                const el = fieldErrorEl(input);
                if (el) { el.textContent = ''; el.classList.add('hidden'); }
            }

            function validateField(name) {
                const input = form.elements[name];
                const rule = rules[name];
                if (!input || !rule) return true;

                const value = input.value.trim();
                if (value === '') {
                    showError(input, rule.empty);
                    return false;
                }
                if (!rule.test(value)) {
                    showError(input, rule.invalid);
                    return false;
                }
                clearError(input);
                return true;
            }

            // Validasi tiap field begitu pelanggan pindah dari situ (blur),
            // supaya kelihatan salahnya sebelum sempat coba submit.
            Object.keys(rules).forEach((name) => {
                const input = form.elements[name];
                if (!input) return;
                input.addEventListener('blur', () => validateField(name));
                input.addEventListener('input', () => {
                    // Begitu mulai dibetulin, error hilang duluan -- jangan
                    // nunggu blur lagi, biar kerasa responsif.
                    if (input.classList.contains('border-red-400')) validateField(name);
                });
            });

            form.addEventListener('submit', function (e) {
                let firstInvalid = null;
                Object.keys(rules).forEach((name) => {
                    const ok = validateField(name);
                    if (!ok && !firstInvalid) firstInvalid = form.elements[name];
                });
                if (firstInvalid) {
                    e.preventDefault();
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        })();
    </script>

    @if($gpsConfig)
        <script>
            (function () {
                const targetLat = {{ $gpsConfig['latitude'] }};
                const targetLng = {{ $gpsConfig['longitude'] }};
                const radius = {{ $gpsConfig['radius'] }};

                const statusBox = document.getElementById('gps-status-box');
                const submitBtn = document.getElementById('submit-ticket-btn');
                const latInput = document.getElementById('input-latitude');
                const lngInput = document.getElementById('input-longitude');

                // Icon SVG kecil buat dipakai di pesan status (innerHTML) -- gaya
                // sama dengan komponen "icon" Blade, cuma di-inline di sini
                // karena ini string JS biasa, bukan render Blade.
                const ICON_PIN = '<svg class="inline-block w-4 h-4 -mt-0.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>';
                const ICON_X = '<svg class="inline-block w-4 h-4 -mt-0.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>';
                const ICON_CHECK = '<svg class="inline-block w-4 h-4 -mt-0.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>';
                const ICON_WARN = '<svg class="inline-block w-4 h-4 -mt-0.5 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>';

                function setStatus(html, colorClasses) {
                    statusBox.innerHTML = html;
                    statusBox.className = 'mb-5 rounded-xl border-2 px-4 py-3 text-sm ' + colorClasses;
                }

                function distanceMeters(lat1, lng1, lat2, lng2) {
                    const R = 6371000;
                    const toRad = (d) => d * Math.PI / 180;
                    const dLat = toRad(lat2 - lat1);
                    const dLng = toRad(lng2 - lng1);
                    const a = Math.sin(dLat / 2) ** 2
                        + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
                    return R * 2 * Math.asin(Math.min(1, Math.sqrt(a)));
                }

                function checkLocation() {
                    if (!navigator.geolocation) {
                        setStatus(ICON_X + 'Browser Anda tidak mendukung deteksi lokasi, jadi tiket tidak bisa diambil dari sini.', 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300');
                        return;
                    }

                    if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                        setStatus(ICON_X + 'Deteksi lokasi butuh koneksi HTTPS. Hubungi petugas di lokasi untuk dibantu membuat tiket.', 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300');
                        return;
                    }

                    setStatus(ICON_PIN + 'Memeriksa lokasi Anda...', 'border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300');
                    submitBtn.disabled = true;

                    navigator.geolocation.getCurrentPosition(function (pos) {
                        const dist = distanceMeters(pos.coords.latitude, pos.coords.longitude, targetLat, targetLng);
                        latInput.value = pos.coords.latitude;
                        lngInput.value = pos.coords.longitude;

                        if (dist <= radius) {
                            setStatus(ICON_CHECK + 'Lokasi terdeteksi, Anda berada di lokasi. Silakan lanjutkan order.', 'border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300');
                            submitBtn.disabled = false;
                        } else {
                            setStatus(ICON_WARN + 'Anda terdeteksi berjarak sekitar ' + Math.round(dist) + ' meter dari lokasi. Pengambilan tiket hanya bisa dilakukan saat Anda berada di ' + @json($location->name) + '.<br><button type="button" id="gps-retry-btn" class="underline font-semibold mt-1">Coba deteksi ulang</button>', 'border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300');
                            submitBtn.disabled = true;
                            bindRetry();
                        }
                    }, function (err) {
                        let msg = 'Gagal mendeteksi lokasi (' + err.message + ').';
                        if (err.code === err.PERMISSION_DENIED) {
                            msg = 'Izin lokasi ditolak. Aktifkan izin lokasi untuk situs ini di pengaturan browser Anda, lalu coba lagi.';
                        }
                        setStatus(ICON_X + msg + '<br><button type="button" id="gps-retry-btn" class="underline font-semibold mt-1">Coba lagi</button>', 'border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300');
                        submitBtn.disabled = true;
                        bindRetry();
                    }, { enableHighAccuracy: true, timeout: 10000 });
                }

                function bindRetry() {
                    const retryBtn = document.getElementById('gps-retry-btn');
                    if (retryBtn) retryBtn.addEventListener('click', checkLocation);
                }

                checkLocation();
            })();
        </script>
    @endif
</x-layout>
