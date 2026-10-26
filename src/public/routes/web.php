<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OperatorController;
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

// Operator routes
Route::middleware(['auth', EnsureUserIsOperator::class])->prefix('operator')->group(function () {
    Route::get('/dashboard', [OperatorController::class, 'dashboard'])->name('operator.dashboard');
    Route::get('/customers', [OperatorController::class, 'customers'])->name('operator.customers');

    // Khusus admin: pilih lokasi tempat "bertugas" sebelum masuk Panel CS
    // (CS/Kasir & Fotografer sudah otomatis ditugaskan, jadi tidak butuh ini).
    Route::get('/choose-location', [OperatorController::class, 'chooseLocation'])->name('operator.choose-location');
    Route::post('/choose-location', [OperatorController::class, 'setLocation'])->name('operator.set-location');

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
    Route::post('/bookings/{booking}/call', [OperatorController::class, 'callBooking'])->name('operator.bookings.call');
    Route::post('/tickets/{ticket}/start', [OperatorController::class, 'startServing'])->name('operator.start-serving');
    Route::post('/tickets/{ticket}/start-editing', [OperatorController::class, 'startEditing'])->name('operator.start-editing');
    Route::post('/tickets/{ticket}/finish-photo-session', [OperatorController::class, 'finishPhotoSession'])->name('operator.finish-photo-session');
    Route::post('/operator/batch-start-editing', [OperatorController::class, 'batchStartEditing'])->name('operator.batch-start-editing');
    Route::post('/counters/{counter}/toggle-pause', [OperatorController::class, 'togglePause'])->name('operator.counters.toggle-pause');
    Route::post('/tickets/{ticket}/complete', [OperatorController::class, 'completeTicket'])->name('operator.complete');
    Route::post('/tickets/{ticket}/complete-registration', [OperatorController::class, 'completeRegistration'])->name('operator.complete-registration');
    Route::post('/tickets/{ticket}/confirm-order', [OperatorController::class, 'confirmOrder'])->name('operator.confirm-order');
    Route::post('/tickets/{ticket}/uncall', [OperatorController::class, 'uncallTicket'])->name('operator.uncall');
    Route::post('/call-next-prepare', [OperatorController::class, 'callNextToPrepare'])->name('operator.call-next-prepare');
    Route::post('/tickets/{ticket}/skip', [OperatorController::class, 'skipTicket'])->name('operator.skip');
    Route::post('/tickets/{ticket}/recall', [OperatorController::class, 'recallTicket'])->name('operator.recall');
    Route::post('/tickets/{ticket}/notes', [OperatorController::class, 'updateNotes'])->name('operator.update-notes');
    Route::get('/tickets/{ticket}/edit', [OperatorController::class, 'editTicket'])->name('operator.tickets.edit');
    Route::put('/tickets/{ticket}', [OperatorController::class, 'updateTicket'])->name('operator.tickets.update');
    Route::delete('/tickets/{ticket}', [OperatorController::class, 'destroyTicket'])->name('operator.tickets.destroy');
});

// Admin routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

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

    // User management
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');

    // Customer master data (dikumpulkan dari histori tiket berdasarkan no. telepon)
    Route::get('/customers', [AdminController::class, 'customers'])->name('admin.customers');
    Route::get('/customers/{phone}', [AdminController::class, 'customerShow'])->name('admin.customers.show');
    Route::put('/customers/{phone}', [AdminController::class, 'customerUpdate'])->name('admin.customers.update');
    Route::delete('/customers/{phone}/tickets/{ticket}', [AdminController::class, 'customerDeleteTicket'])->name('admin.customers.tickets.delete');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports');

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