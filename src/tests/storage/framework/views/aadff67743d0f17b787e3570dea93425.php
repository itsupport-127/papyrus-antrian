<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => $location->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($location->name)]); ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <a href="<?php echo e(route('locations.index')); ?>" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo e($location->name); ?></h1>
                <p class="text-slate-600 dark:text-slate-400"><?php echo e($location->description); ?></p>
            </div>
            <?php if($location->offersWalkInTicket()): ?>
                <a href="<?php echo e(route('tickets.create', $location)); ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                    Ambil Tiket
                </a>
            <?php elseif($location->offersBooking()): ?>
                <a href="<?php echo e(route('bookings.create', $location)); ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                    Reservasi
                </a>
            <?php endif; ?>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Currently Serving -->
            <div class="lg:col-span-2">
                <div id="currently-serving-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Sedang Dilayani</h2>

                    <?php if($currentlyServing->isEmpty()): ?>
                        <p class="text-slate-500 dark:text-slate-400 text-center py-8">Belum ada yang dilayani</p>
                    <?php else: ?>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <?php $__currentLoopData = $currentlyServing; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-2xl font-bold text-green-700 dark:text-green-400"><?php echo e($ticket->ticket_number); ?></span>
                                        <span class="text-sm text-green-600 dark:text-green-400"><?php echo e($ticket->counter?->name); ?></span>
                                    </div>
                                    <?php if($ticket->customer_name): ?>
                                        <p class="text-green-600 dark:text-green-400 text-sm mt-1"><?php echo e($ticket->customer_name); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Waiting Queue -->
                <div id="waiting-queue-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 mt-6">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                        Antrian Menunggu
                        <span class="text-sm font-normal text-slate-500 dark:text-slate-400">(<?php echo e($waitingTickets->count()); ?> orang)</span>
                    </h2>

                    <?php if($waitingTickets->isEmpty()): ?>
                        <p class="text-slate-500 dark:text-slate-400 text-center py-8">Tidak ada antrian</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php $__currentLoopData = $waitingTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-center justify-between py-3 <?php echo e(!$loop->last ? 'border-b border-slate-100 dark:border-slate-700' : ''); ?>">
                                    <div class="flex items-center space-x-4">
                                        <span class="w-8 h-8 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center text-sm font-medium text-slate-600 dark:text-slate-300">
                                            <?php echo e($index + 1); ?>

                                        </span>
                                        <div>
                                            <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($ticket->ticket_number); ?></span>
                                            <?php if($ticket->customer_name): ?>
                                                <span class="text-slate-500 dark:text-slate-400 text-sm ml-2"><?php echo e($ticket->customer_name); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
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
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo e($location->code); ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Jam Operasional</dt>
                            <dd class="font-medium text-slate-900 dark:text-white">
                                <?php echo e(\Carbon\Carbon::parse($location->open_time)->format('H:i')); ?> - <?php echo e(\Carbon\Carbon::parse($location->close_time)->format('H:i')); ?>

                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Loket Aktif</dt>
                            <dd class="font-medium text-slate-900 dark:text-white"><?php echo e($location->counters->count()); ?></dd>
                        </div>
                    </dl>
                </div>

                <!-- Counters -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Loket</h3>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $location->counters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $counter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between py-2 <?php echo e(!$loop->last ? 'border-b border-slate-100 dark:border-slate-700' : ''); ?>">
                                <span class="font-medium text-slate-900 dark:text-white"><?php echo e($counter->name); ?></span>
                                <?php if($counter->currentOperator): ?>
                                    <span class="text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-1 rounded">Aktif</span>
                                <?php else: ?>
                                    <span class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 px-2 py-1 rounded">Tidak Aktif</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <!-- Display Link -->
                <a href="<?php echo e(route('locations.display', $location)); ?>" target="_blank" class="block bg-blue-900 hover:bg-blue-800 dark:bg-blue-950 dark:hover:bg-blue-900 text-white text-center py-3 rounded-lg font-medium">
                    Buka Tampilan Display
                </a>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
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
            window.Echo.channel('location.<?php echo e($location->id); ?>')
                .listen('.ticket.created', refreshQueue)
                .listen('.ticket.called', refreshQueue)
                .listen('.ticket.status.changed', refreshQueue);
        }
    </script>
    <?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal23a33f287873b564aaf305a1526eada4)): ?>
<?php $attributes = $__attributesOriginal23a33f287873b564aaf305a1526eada4; ?>
<?php unset($__attributesOriginal23a33f287873b564aaf305a1526eada4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal23a33f287873b564aaf305a1526eada4)): ?>
<?php $component = $__componentOriginal23a33f287873b564aaf305a1526eada4; ?>
<?php unset($__componentOriginal23a33f287873b564aaf305a1526eada4); ?>
<?php endif; ?>
<?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/locations/show.blade.php ENDPATH**/ ?>