<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingExtraController;
use App\Http\Controllers\BookingPackageController;
use App\Http\Controllers\ReminderSettingController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TicketController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsOperator;
use App\Models\Counter;
use App\Models\Location;
use App\Models\Ticket;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    $locations = Location::where('is_active', true)
        ->withCount(['tickets as waiting_count' => fn ($q) => $q->where('status', 'waiting')])
        ->get()
        ->filter(fn ($l) => $l->offersWalkInTicket())
        ->values();
    $stats = [
        'locations' => Location::where('is_active', true)->count(),
        'counters' => Counter::where('is_active', true)->count(),
        'today_tickets' => Ticket::whereDate('created_at', today())->count(),
        'today_called' => Ticket::whereDate('created_at', today())->whereNotNull('called_at')->count(),
    ];
    return view('home', compact('locations', 'stats'));
})->name('home');

// Location routes (public)
Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
// Route spesifik ("display-all") WAJIB didaftarkan SEBELUM
// '/locations/{location}' -- kalau kebalik, Laravel bakal coba
// interpretasikan "display-all" sebagai {location} (route model binding),
// bukan cocok ke sini, dan bakal 404 "Location not found" alih-alih
// nampilin halaman gabungan.
Route::get('/locations/display-all', [LocationController::class, 'displayAll'])->name('locations.display-all');
Route::get('/locations/{location}', [LocationController::class, 'show'])->name('locations.show');
Route::get('/locations/{location}/display', [LocationController::class, 'display'])->name('locations.display');
Route::get('/locations/{location}/scan', [LocationController::class, 'scan'])->name('locations.scan');

// Ticket routes (public)
Route::get('/tickets/check', [TicketController::class, 'check'])->name('tickets.check');
Route::get('/locations/{location}/ticket', [TicketController::class, 'create'])->name('tickets.create');
Route::post('/locations/{location}/ticket', [TicketController::class, 'store'])->name('tickets.store');
Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');

// Push subscription routes (public, PWA notifications)
Route::post('/tickets/{ticket}/push-subscribe', [PushSubscriptionController::class, 'store'])->name('tickets.push-subscribe');
Route::post('/tickets/{ticket}/push-unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('tickets.push-unsubscribe');

// Feedback routes (public)
Route::post('/tickets/{ticket}/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

// Booking routes (public)
Route::get('/bookings/check', [BookingController::class, 'check'])->name('bookings.check');
Route::get('/locations/{location}/booking', [BookingController::class, 'create'])->name('bookings.create');
Route::get('/locations/{location}/booking/slots', [BookingController::class, 'availableSlots'])->name('bookings.slots');
Route::get('/locations/{location}/booking/taken-times', [BookingController::class, 'takenTimes'])->name('bookings.taken-times');
Route::post('/locations/{location}/booking', [BookingController::class, 'store'])->name('bookings.store');
Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in');
Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
Route::put('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Profil staf: ubah nama, foto (otomatis dikompres), dan password
Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::post('/profil/foto', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::delete('/profil/foto', [ProfileController::class, 'removePhoto'])->name('profile.photo.remove');
    Route::get('/avatar/{user}', [ProfileController::class, 'avatar'])->name('profile.avatar');
});

// Operator routes
Route::middleware(['auth', EnsureUserIsOperator::class])->prefix('operator')->group(function () {
    Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('operator.dashboard');
    Route::get('/customers', [OperatorController::class, 'customers'])->name('operator.customers');

    // Khusus admin: pilih lokasi tempat "bertugas" sebelum masuk Panel CS
    // (CS/Kasir & Fotografer sudah otomatis ditugaskan, jadi tidak butuh ini).
    Route::get('/choose-location', [OperatorController::class, 'chooseLocation'])->name('operator.choose-location');
    Route::post('/choose-location', [OperatorController::class, 'setLocation'])->name('operator.set-location');

    // Pilih loket/studio -- WAJIB dipilih manual sekali tiap sesi login
    // (CS & Kasir maupun Fotografer). Nempel terus sampai logout (lihat
    // AuthController::logout yang ngelepasnya), abis itu harus pilih lagi.
    Route::get('/choose-counter', [OperatorController::class, 'chooseCounter'])->name('operator.choose-counter');
    Route::post('/choose-counter', [OperatorController::class, 'setCounter'])->name('operator.set-counter');
    Route::get('/release-counter', [OperatorController::class, 'releaseCounter'])->name('operator.release-counter');

    // Jadwal Reservasi — khusus CS & Kasir, otomatis scoped ke lokasi mereka sendiri.
    Route::get('/booking-slots', [OperatorController::class, 'bookingSlots'])->name('operator.booking-slots');
    Route::post('/booking-slots/mode', [OperatorController::class, 'updateBookingMode'])->name('operator.booking-slots.mode');
    Route::post('/booking-slots/bulk', [OperatorController::class, 'bulkActionBookingSlots'])->name('operator.booking-slots.bulk');
    Route::post('/booking-slots', [OperatorController::class, 'storeBookingSlot'])->name('operator.booking-slots.store');
    Route::post('/booking-slots/generate', [OperatorController::class, 'generateBookingSlots'])->name('operator.booking-slots.generate');
    Route::put('/booking-slots/{bookingSlot}', [OperatorController::class, 'updateBookingSlot'])->name('operator.booking-slots.update');
    Route::post('/booking-slots/{bookingSlot}/toggle', [OperatorController::class, 'toggleBookingSlot'])->name('operator.booking-slots.toggle');
    Route::delete('/booking-slots/{bookingSlot}', [OperatorController::class, 'deleteBookingSlot'])->name('operator.booking-slots.delete');
    Route::post('/call-next', [OperatorController::class, 'callNext'])->name('operator.call-next');
    Route::post('/tickets/{ticket}/call', [OperatorController::class, 'callSpecific'])->name('operator.call-specific');
    Route::get('/bookings/create', [OperatorController::class, 'createBooking'])->name('operator.bookings.create');
    Route::post('/bookings', [OperatorController::class, 'storeManualBooking'])->name('operator.bookings.store');
    Route::get('/bookings/{booking}/edit', [OperatorController::class, 'editBooking'])->name('operator.bookings.edit');
    Route::put('/bookings/{booking}', [OperatorController::class, 'updateBooking'])->name('operator.bookings.update');
    Route::delete('/bookings/{booking}', [OperatorController::class, 'destroyBooking'])->name('operator.bookings.destroy');
    Route::delete('/bookings/{booking}/remove', [OperatorController::class, 'removeBooking'])->name('operator.bookings.remove');
    Route::post('/bookings/{booking}/call', [OperatorController::class, 'callBooking'])->name('operator.bookings.call');
    Route::post('/tickets/{ticket}/start', [OperatorController::class, 'startServing'])->name('operator.start-serving');
    Route::post('/tickets/{ticket}/start-editing', [OperatorController::class, 'startEditing'])->name('operator.start-editing');
    Route::post('/tickets/{ticket}/finish-photo-session', [OperatorController::class, 'finishPhotoSession'])->name('operator.finish-photo-session');
    Route::post('/tickets/{ticket}/call-photo-selection', [OperatorController::class, 'callPhotoSelection'])->name('operator.call-photo-selection');
    Route::post('/tickets/{ticket}/begin-photo-selection', [OperatorController::class, 'beginPhotoSelection'])->name('operator.begin-photo-selection');
    Route::post('/tickets/{ticket}/finish-photo-selection', [OperatorController::class, 'finishPhotoSelection'])->name('operator.finish-photo-selection');
    Route::post('/tickets/{ticket}/edit-progress', [OperatorController::class, 'updateEditProgress'])->name('operator.edit-progress');
    Route::post('/tickets/{ticket}/extend-photo-selection', [OperatorController::class, 'extendPhotoSelection'])->name('operator.extend-photo-selection');
    Route::post('/operator/photo-select-setting', [OperatorController::class, 'updatePhotoSelectSetting'])->name('operator.photo-select-setting');
    Route::post('/operator/batch-start-editing', [OperatorController::class, 'batchStartEditing'])->name('operator.batch-start-editing');
    Route::post('/tickets/{ticket}/takeover', [OperatorController::class, 'takeoverTicket'])->name('operator.takeover');
    Route::post('/operator/takeover-all', [OperatorController::class, 'takeoverAllTickets'])->name('operator.takeover-all');
    Route::post('/tickets/{ticket}/skip-to-payment-pending-edit', [OperatorController::class, 'skipToPaymentPendingEdit'])->name('operator.skip-to-payment-pending-edit');
    Route::post('/tickets/{ticket}/mark-edit-completed', [OperatorController::class, 'markEditCompleted'])->name('operator.mark-edit-completed');
    Route::post('/tickets/{ticket}/mark-picked-up', [OperatorController::class, 'markPickedUp'])->name('operator.mark-picked-up');
    Route::post('/counters/{counter}/toggle-pause', [OperatorController::class, 'togglePause'])->name('operator.counters.toggle-pause');
    Route::post('/tickets/{ticket}/complete', [OperatorController::class, 'completeTicket'])->name('operator.complete');
    Route::post('/tickets/{ticket}/complete-registration', [OperatorController::class, 'completeRegistration'])->name('operator.complete-registration');
    Route::post('/tickets/{ticket}/confirm-order', [OperatorController::class, 'confirmOrder'])->name('operator.confirm-order');
    Route::post('/tickets/{ticket}/uncall', [OperatorController::class, 'uncallTicket'])->name('operator.uncall');
    Route::post('/call-next-prepare', [OperatorController::class, 'callNextToPrepare'])->name('operator.call-next-prepare');
    Route::post('/tickets/{ticket}/call-to-prepare', [OperatorController::class, 'callSpecificToPrepare'])->name('operator.call-specific-prepare');
    Route::post('/call-next-prepare-auto', [OperatorController::class, 'callNextToPrepareAuto'])->name('operator.call-next-prepare-auto');
    Route::post('/tickets/{ticket}/call-to-prepare-auto', [OperatorController::class, 'callSpecificToPrepareAuto'])->name('operator.call-specific-prepare-auto');
    Route::post('/tickets/{ticket}/call-preparing', [OperatorController::class, 'callPreparingTicket'])->name('operator.call-preparing');
    Route::post('/tickets/{ticket}/skip', [OperatorController::class, 'skipTicket'])->name('operator.skip');
    Route::post('/tickets/{ticket}/recall', [OperatorController::class, 'recallTicket'])->name('operator.recall');
    Route::post('/tickets/{ticket}/recall-preparing', [OperatorController::class, 'recallPreparingTicket'])->name('operator.recall-preparing');
    Route::post('/tickets/{ticket}/mark-ready', [OperatorController::class, 'markReady'])->name('operator.mark-ready');
    Route::post('/tickets/{ticket}/unmark-ready', [OperatorController::class, 'unmarkReady'])->name('operator.unmark-ready');
    Route::post('/tickets/{ticket}/notes', [OperatorController::class, 'updateNotes'])->name('operator.update-notes');
    Route::post('/tickets/{ticket}/notes/unlock', [OperatorController::class, 'unlockNotes'])->name('operator.unlock-notes');
    Route::get('/tickets/{ticket}/edit', [OperatorController::class, 'editTicket'])->name('operator.tickets.edit');
    Route::put('/tickets/{ticket}', [OperatorController::class, 'updateTicket'])->name('operator.tickets.update');
    Route::delete('/tickets/{ticket}', [OperatorController::class, 'destroyTicket'])->name('operator.tickets.destroy');
    Route::delete('/tickets/{ticket}/remove', [OperatorController::class, 'removeTicket'])->name('operator.tickets.remove');
});

// Admin routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/notifications-summary', [AdminController::class, 'notificationsSummary'])->name('admin.notifications-summary');

    // Location management
    Route::get('/locations', [AdminController::class, 'locations'])->name('admin.locations');
    Route::get('/locations/create', [AdminController::class, 'createLocation'])->name('admin.locations.create');
    Route::post('/locations', [AdminController::class, 'storeLocation'])->name('admin.locations.store');
    Route::get('/locations/{location}/edit', [AdminController::class, 'editLocation'])->name('admin.locations.edit');
    Route::put('/locations/{location}', [AdminController::class, 'updateLocation'])->name('admin.locations.update');
    Route::delete('/locations/{location}', [AdminController::class, 'deleteLocation'])->name('admin.locations.delete');

    // Counter management
    Route::get('/locations/{location}/counters', [AdminController::class, 'counters'])->name('admin.counters');
    Route::post('/locations/{location}/counters', [AdminController::class, 'storeCounter'])->name('admin.counters.store');
    Route::put('/counters/{counter}', [AdminController::class, 'updateCounter'])->name('admin.counters.update');
    Route::post('/counters/{counter}/toggle', [AdminController::class, 'toggleCounter'])->name('admin.counters.toggle');
    Route::delete('/counters/{counter}', [AdminController::class, 'deleteCounter'])->name('admin.counters.delete');

    // Pengingat di dashboard operator (Ready / pembayaran / selesai edit)
    Route::get('/reminders', [ReminderSettingController::class, 'index'])->name('admin.reminders');
    Route::put('/reminders', [ReminderSettingController::class, 'update'])->name('admin.reminders.update');

    // Paket Reservasi (isi dropdown "Pilihan Paket" di form reservasi)
    Route::get('/booking-packages', [BookingPackageController::class, 'index'])->name('admin.booking-packages');
    Route::post('/booking-packages', [BookingPackageController::class, 'store'])->name('admin.booking-packages.store');
    Route::put('/booking-packages/{package}', [BookingPackageController::class, 'update'])->name('admin.booking-packages.update');
    Route::post('/booking-packages/{package}/toggle', [BookingPackageController::class, 'toggle'])->name('admin.booking-packages.toggle');
    Route::post('/booking-packages/{package}/move', [BookingPackageController::class, 'move'])->name('admin.booking-packages.move');
    Route::delete('/booking-packages/{package}', [BookingPackageController::class, 'destroy'])->name('admin.booking-packages.destroy');

    // Tambahan Reservasi (isi dropdown "Tambahan" di form reservasi)
    Route::get('/booking-extras', [BookingExtraController::class, 'index'])->name('admin.booking-extras');
    Route::post('/booking-extras', [BookingExtraController::class, 'store'])->name('admin.booking-extras.store');
    Route::put('/booking-extras/{extra}', [BookingExtraController::class, 'update'])->name('admin.booking-extras.update');
    Route::post('/booking-extras/{extra}/toggle', [BookingExtraController::class, 'toggle'])->name('admin.booking-extras.toggle');
    Route::post('/booking-extras/{extra}/move', [BookingExtraController::class, 'move'])->name('admin.booking-extras.move');
    Route::delete('/booking-extras/{extra}', [BookingExtraController::class, 'destroy'])->name('admin.booking-extras.destroy');

    // User management
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');
    Route::get('/reports/staff', [ReportController::class, 'staffIndex'])->name('admin.reports.staff.index');
    Route::get('/reports/staff/{user}', [ReportController::class, 'staff'])->name('admin.reports.staff');

    // Service Category management
    Route::get('/locations/{location}/categories', [AdminController::class, 'serviceCategories'])->name('admin.categories');
    Route::post('/locations/{location}/categories', [AdminController::class, 'storeServiceCategory'])->name('admin.categories.store');
    Route::post('/categories/{category}/toggle', [AdminController::class, 'toggleServiceCategory'])->name('admin.categories.toggle');
    Route::delete('/categories/{category}', [AdminController::class, 'deleteServiceCategory'])->name('admin.categories.delete');

    Route::get('/locations/{location}/booking-slots', [AdminController::class, 'bookingSlots'])->name('admin.booking-slots');
    Route::post('/locations/{location}/booking-slots/mode', [AdminController::class, 'updateBookingMode'])->name('admin.booking-slots.mode');
    Route::post('/locations/{location}/booking-slots/copy', [AdminController::class, 'copyBookingSettings'])->name('admin.booking-slots.copy');
    Route::post('/locations/{location}/booking-slots/bulk', [AdminController::class, 'bulkActionBookingSlots'])->name('admin.booking-slots.bulk');
    Route::post('/locations/{location}/booking-slots', [AdminController::class, 'storeBookingSlot'])->name('admin.booking-slots.store');
    Route::post('/locations/{location}/booking-slots/generate', [AdminController::class, 'generateBookingSlots'])->name('admin.booking-slots.generate');
    Route::put('/booking-slots/{bookingSlot}', [AdminController::class, 'updateBookingSlot'])->name('admin.booking-slots.update');
    Route::post('/booking-slots/{bookingSlot}/toggle', [AdminController::class, 'toggleBookingSlot'])->name('admin.booking-slots.toggle');
    Route::delete('/booking-slots/{bookingSlot}', [AdminController::class, 'deleteBookingSlot'])->name('admin.booking-slots.delete');
});

// Master Data Pelanggan -- dikumpulkan dari histori tiket lintas lokasi
// berdasarkan no. telepon. Sengaja DIPISAH dari grup /admin di atas (yang
// admin-only): CS & Kasir juga perlu akses ini dari nav Panel-nya (beda
// dari tab "Data Pelanggan" yang cuma nampilin tiket HARI INI di lokasi
// dia -- ini rekap semua histori pelanggan, lintas hari & lintas lokasi).
// Tetap di bawah URL prefix /admin/customers (link lama yang mungkin
// sudah dibagikan/di-bookmark tetap jalan), cuma middleware-nya diganti
// EnsureUserIsOperator (operator/fotografer/admin) bukan EnsureUserIsAdmin
// (admin doang).
Route::middleware(['auth', EnsureUserIsOperator::class])->prefix('admin')->group(function () {
    Route::get('/customers', [AdminController::class, 'customers'])->name('admin.customers');
    Route::get('/customers/{phone}', [AdminController::class, 'customerShow'])->name('admin.customers.show');
    Route::put('/customers/{phone}', [AdminController::class, 'customerUpdate'])->name('admin.customers.update');
    Route::delete('/customers/{phone}/tickets/{ticket}', [AdminController::class, 'customerDeleteTicket'])->name('admin.customers.tickets.delete');
});