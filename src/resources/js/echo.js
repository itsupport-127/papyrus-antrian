import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;

// Kalau VITE_REVERB_APP_KEY / VITE_REVERB_HOST belum diisi di .env, `new Echo(...)`
// di bawah akan melempar error dan window.Echo TIDAK PERNAH ke-assign — inilah
// penyebab error "Cannot read properties of undefined (reading 'channel')" di
// halaman display. Jadi kalau konfigurasinya belum ada, jangan coba konek sama
// sekali; biarkan window.Echo tetap undefined dan setiap pemanggil
// (window.Echo.channel(...)) sudah dijaga dengan `if (window.Echo)`.
if (reverbKey && reverbHost) {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });
    } catch (err) {
        console.warn('Gagal menyambungkan Echo/Reverb, realtime dinonaktifkan:', err);
    }
} else {
    console.warn(
        'VITE_REVERB_APP_KEY / VITE_REVERB_HOST belum diatur di .env — realtime (Echo) dinonaktifkan, halaman akan pakai auto-refresh biasa.'
    );
}
