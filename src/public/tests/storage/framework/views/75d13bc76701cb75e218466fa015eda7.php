<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Panel Operator']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Panel Operator']); ?>
    <?php $__env->startPush('scripts'); ?>
    <script>
        const grid = document.getElementById('operator-grid');
        const tabs = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.tab-panel');

        function switchTab(targetId) {
            tabs.forEach(tab => {
                tab.classList.remove('active', 'border-blue-600', 'text-blue-600', 'dark:text-blue-400');
                tab.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
            });
            panels.forEach(panel => panel.classList.add('hidden'));
            const targetTab = document.querySelector(`[data-tab="${targetId}"]`);
            const targetPanel = document.getElementById(targetId);
            if (targetTab) {
                targetTab.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
                targetTab.classList.add('active', 'border-blue-600', 'text-blue-600', 'dark:text-blue-400');
            }
            if (targetPanel) targetPanel.classList.remove('hidden');
            if (grid) grid.classList.toggle('hidden', targetId === 'tab-pelanggan');
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-tab');
                switchTab(target);
            });
        });

        const hash = window.location.hash.replace('#', '');
        if (hash && document.getElementById(hash)) {
            switchTab(hash);
        }

        async function refreshDashboard() {
            const statusEl = document.getElementById('refresh-status');
            try {
                const url = window.location.href.split('#')[0].split('?')[0] + '?_=' + Date.now();
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                });
                if (!response.ok) return;
                if (response.redirected && response.url !== window.location.href.split('#')[0].split('?')[0]) return;
                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');

                const activeTab = document.querySelector('.tab-btn.active');
                const activeTabId = activeTab ? activeTab.getAttribute('data-tab') : null;

                const refreshIds = ['stats-section', 'current-ticket-panel', 'tab-antrian', 'tab-reservasi', 'tab-pelanggan'];
                let updated = false;
                refreshIds.forEach(id => {
                    const oldEl = document.getElementById(id);
                    const newEl = doc.getElementById(id);
                    if (!oldEl || !newEl) return;
                    const active = document.activeElement;
                    if (active && oldEl.contains(active) && active !== document.body) return;
                    if (oldEl.innerHTML !== newEl.innerHTML) {
                        updated = true;
                    }
                    oldEl.innerHTML = newEl.innerHTML;
                });

                if (activeTabId && document.querySelector(`[data-tab="${activeTabId}"]`)) {
                    switchTab(activeTabId);
                }

                if (updated && statusEl) {
                    statusEl.textContent = 'Updated ' + new Date().toLocaleTimeString();
                    statusEl.classList.add('text-green-600', 'dark:text-green-400');
                    setTimeout(() => {
                        statusEl.textContent = 'Auto-refresh ON';
                        statusEl.classList.remove('text-green-600', 'dark:text-green-400');
                    }, 2000);
                }
            } catch (err) {
                console.warn('Auto-refresh dashboard failed:', err);
            }
        }

        setInterval(refreshDashboard, 2000);
        refreshDashboard();

        if (window.Echo) {
            window.Echo.channel('location.<?php echo e($location->id); ?>')
                .listen('.ticket.created', refreshDashboard)
                .listen('.ticket.called', refreshDashboard)
                .listen('.ticket.status.changed', refreshDashboard)
                .listen('.queue.updated', refreshDashboard);
        }

        window.refreshDashboard = refreshDashboard;
    </script>
    <?php $__env->stopPush(); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <!-- Top Bar -->
        <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-20">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10">
                <div class="flex items-center justify-between gap-2 h-14">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 bg-gradient-to-br from-lavender via-skyblue to-dustyrose rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                            <?php if(file_exists(public_path('images/logo-papyrus.png'))): ?>
                                <img src="<?php echo e(asset('images/logo-papyrus.png')); ?>" alt="Papyrus Photo" class="w-full h-full object-contain">
                            <?php else: ?>
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-base font-bold text-slate-900 dark:text-white truncate">Panel <?php echo e($counter->name ?? 'CS & Kasir'); ?></h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?php echo e($counterMessage ?? 'Semua Lokasi'); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span id="refresh-status" class="text-[11px] text-slate-400 dark:text-slate-500 hidden sm:inline">Auto-refresh ON</span>
                        <button type="button" onclick="window.refreshDashboard && window.refreshDashboard()" class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold transition-colors" title="Refresh data sekarang">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span class="hidden xs:inline sm:inline">Refresh</span>
                        </button>
                        <?php if($counter): ?>
                            <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400"><?php echo e($counter->stageLabel()); ?></span>
                            <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse flex-shrink-0" title="Loket aktif"></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-6">
            <form method="GET" action="<?php echo e(route('operator.dashboard')); ?>" class="mb-6">
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <div class="flex items-center gap-2">
                        <label for="location-filter" class="text-sm font-semibold text-slate-700 dark:text-slate-300 whitespace-nowrap">Lokasi:</label>
                        <select id="location-filter" name="location_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Lokasi</option>
                            <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($loc->id); ?>" <?php echo e((request('location_id') == $loc->id) ? 'selected' : ''); ?>><?php echo e($loc->name); ?> (<?php echo e($loc->code); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <input type="hidden" name="date" value="<?php echo e(request('date', now()->toDateString())); ?>" />
                    <input type="hidden" name="q" value="<?php echo e(request('q', '')); ?>" />
                    <input type="hidden" name="status" value="<?php echo e(request('status', '')); ?>" />
                    <input type="hidden" name="stage" value="<?php echo e(request('stage', '')); ?>" />
                </div>
            </form>

            <!-- Stats -->
            <div id="stats-section" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Total Lokasi</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e($todayStats['total_locations']); ?></p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Total Tiket Hari Ini</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1"><?php echo e($todayStats['total']); ?></p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Sedang Dilayani</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1"><?php echo e($todayStats['serving']); ?></p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-slate-500 dark:text-slate-400">Selesai</p>
                    <p class="text-2xl font-bold text-green-600 mt-1"><?php echo e($todayStats['served']); ?></p>
                </div>
            </div>

            <?php if(!$counter): ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden" id="no-counter-panel">
                    <div class="p-10 text-center">
                        <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Loket Belum Siap</h2>
                        <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto"><?php echo e($counterMessage ?? 'Menunggu loket tersedia untuk role Anda.'); ?></p>
                        <p class="text-slate-400 dark:text-slate-500 text-sm mt-4">Halaman ini akan dimuat ulang secara otomatis.</p>
                    </div>
                </div>
            <?php else: ?>
                    <!-- Section Tabs -->
                    <div class="flex items-center gap-1 mb-6 border-b border-slate-200 dark:border-slate-700 overflow-x-auto">
                        <button type="button" data-tab="tab-antrian" class="tab-btn active border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                            <?php echo e($isFotografer ? 'Antrian Sesi Foto' : 'Antrian Loket'); ?>

                        </button>
                        <?php if($counter && $counter->handlesStage('registration') && in_array('booking', $permissions)): ?>
                            <button type="button" data-tab="tab-reservasi" class="tab-btn border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                                Reservasi Hari Ini
                                <?php if($todayBookings->isNotEmpty()): ?>
                                    <span class="ml-1.5 inline-flex items-center justify-center px-1.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400"><?php echo e($todayBookings->count()); ?></span>
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                        <?php if(in_array('customers', $permissions)): ?>
                            <button type="button" data-tab="tab-pelanggan" class="tab-btn border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-4 py-3 text-sm font-semibold whitespace-nowrap transition-colors">
                                Data Pelanggan
                            </button>
                        <?php endif; ?>
                    </div>

                    <div id="operator-grid" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 space-y-6">
                        <div id="tab-antrian" class="tab-panel space-y-6">
                        <!-- Current Ticket Panel -->
                        <?php if(in_array('walkin', $permissions) || in_array('photo', $permissions)): ?>
                            <div id="current-ticket-panel" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white"><?php echo e($counter->stageLabel()); ?></h2>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">TIKET AKTIF</span>
                                </div>
                                <div class="p-4">
                                    <?php if($currentTicket): ?>
                                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 text-center mb-4">
                                            <p class="text-blue-600 dark:text-blue-300 font-medium mb-1 text-sm">
                                                <?php if($currentTicket->status === 'calling'): ?>
                                                    <span class="inline-flex items-center"><span class="w-2 h-2 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>Memanggil...</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center"><span class="w-2 h-2 bg-blue-500 rounded-full mr-1.5"></span>Sedang Dilayani</span>
                                                <?php endif; ?>
                                            </p>
                                            <p class="text-5xl font-black text-blue-900 dark:text-blue-200"><?php echo e($currentTicket->ticket_number); ?></p>
                                            <?php if($currentTicket->customer_name): ?>
                                                <p class="text-blue-600 dark:text-blue-300 mt-2 text-base"><?php echo e($currentTicket->customer_name); ?></p>
                                            <?php endif; ?>
                                            <p class="text-blue-500 dark:text-blue-400 text-xs mt-1">
                                                <?php echo e($currentTicket->customer_phone); ?>

                                                <?php if($currentTicket->customer_email): ?>
                                                    &middot; <?php echo e($currentTicket->customer_email); ?>

                                                <?php endif; ?>
                                            </p>
                                            <?php if($currentTicket->skip_count > 0): ?>
                                                <p class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                    Dilewati <?php echo e($currentTicket->skip_count); ?>x
                                                    <?php if($currentTicket->skip_count + 1 >= \App\Models\Ticket::MAX_SKIPS): ?>
                                                        &middot; akan dibatalkan otomatis jika dilewati lagi
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($currentTicket->status === 'calling'): ?>
                                            <form method="POST" action="<?php echo e(route('operator.recall', $currentTicket)); ?>" class="mb-3">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-700 border-2 border-blue-600 dark:border-blue-500 text-blue-600 dark:text-blue-400 py-2.5 rounded-lg font-semibold transition-colors text-sm hover:bg-blue-50 dark:hover:bg-slate-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                    Panggil Ulang
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if($currentTicket->stage === 'registration'): ?>
                                            <?php if($currentTicket->status === 'calling'): ?>
                                                <form method="POST" action="<?php echo e(route('operator.start-serving', $currentTicket)); ?>" class="mb-3">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="w-full flex items-center justify-center bg-amber-600 hover:bg-amber-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Mulai Melayani</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="<?php echo e(route('operator.complete-registration', $currentTicket)); ?>" class="mb-3" id="registration-form">
                                                    <?php echo csrf_field(); ?>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Gunakan kolom "Catatan" di bawah untuk mencatat kebutuhan pelanggan bila perlu, lalu lanjutkan ke Sesi Foto.</p>
                                                    <button type="submit" class="w-full flex items-center justify-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Selesaikan, Lanjut ke Sesi Foto</button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <form method="POST" action="<?php echo e(route('operator.complete', $currentTicket)); ?>" class="mb-3">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="w-full flex items-center justify-center bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm"><?php echo e($currentTicket->stage === 'payment' ? 'Selesai (Bayar & Cetak)' : 'Selesaikan, Lanjut ke Pembayaran'); ?></button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="<?php echo e(route('operator.skip', $currentTicket)); ?>" class="mb-4">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="w-full flex items-center justify-center bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg font-semibold transition-colors text-sm">Lewati (Panggil Nanti)</button>
                                        </form>
                                        <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                                            <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Catatan</h4>
                                            <form method="POST" action="<?php echo e(route('operator.update-notes', $currentTicket)); ?>" class="flex gap-2 items-end">
                                                <?php echo csrf_field(); ?>
                                                <textarea name="notes" rows="1" placeholder="Catatan dari CS / Kasir / Fotografer..." class="flex-1 px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm resize-none"><?php echo e($currentTicket->notes); ?></textarea>
                                                <button type="submit" class="px-3 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-xs font-semibold">Simpan</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <?php
                                            $autoStages = array_diff($counter->stages ?? [], ['payment']);
                                            $autoWaitingCount = collect($autoStages)->sum(fn($s) => $waitingByStage[$s]->count() ?? 0);
                                            $paymentWaitingCount = in_array('payment', $counter->stages ?? [], true) ? ($waitingByStage['payment']->count() ?? 0) : 0;
                                            $totalWaitingCount = $autoWaitingCount + $paymentWaitingCount;
                                        ?>
                                        <div class="text-center py-12">
                                            <?php if($autoWaitingCount === 0 && $paymentWaitingCount > 0): ?>
                                                <p class="text-slate-500 dark:text-slate-400 mb-2">Antrian Registrasi kosong.</p>
                                                <p class="text-slate-500 dark:text-slate-400 mb-6">Ada <strong><?php echo e($paymentWaitingCount); ?></strong> tiket menunggu Pembayaran & Cetak Foto.</p>
                                            <?php else: ?>
                                                <p class="text-slate-500 dark:text-slate-400 mb-6">Tidak ada tiket yang sedang dilayani di tahap <strong><?php echo e($counter->stageLabel()); ?></strong></p>
                                            <?php endif; ?>
                                            <form method="POST" action="<?php echo e(route('operator.call-next')); ?>">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="location_id" value="<?php echo e(request('location_id')); ?>" />
                                                <button type="submit"
                                                    <?php if($totalWaitingCount === 0): ?> disabled <?php endif; ?>
                                                    class="inline-flex items-center justify-center w-full sm:w-auto bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 disabled:bg-slate-300 disabled:cursor-not-allowed text-white px-6 sm:px-10 py-4 sm:py-5 rounded-2xl font-bold text-base sm:text-lg transition-colors shadow-lg shadow-blue-200 disabled:shadow-none">Panggil Antrian Berikutnya</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Waiting Queue -->
                        <?php if($counter && in_array('walkin', $permissions)): ?>
                            <?php
                                $isSingleStageCounter = $counter && count($counter->stages ?? []) === 1;
                                $stagesToShow = $isSingleStageCounter ? $counter->stages : \App\Models\Ticket::STAGE_ORDER;
                            ?>
                            <div id="waiting-queue-panel" class="space-y-6">
                                <?php $__currentLoopData = $stagesToShow; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $stageTickets = $waitingByStage[$stage] ?? collect(); ?>
                                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                                        <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                            <div>
                                                <h2 class="text-xl font-bold text-slate-900 dark:text-white"><?php echo e($isSingleStageCounter ? 'Daftar Tunggu' : 'Antrian ' . (\App\Models\Ticket::STAGE_LABELS[$stage] ?? $stage)); ?></h2>
                                                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1"><?php echo e($stageTickets->count()); ?> tiket menunggu</p>
                                            </div>
                                            <?php if(!$currentTicket && $stageTickets->isNotEmpty() && in_array($stage, $counter->stages ?? [], true)): ?>
                                                <form method="POST" action="<?php echo e(route('operator.call-next')); ?>" class="inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="stage" value="<?php echo e($stage); ?>" />
                                                    <input type="hidden" name="location_id" value="<?php echo e(request('location_id')); ?>" />
                                                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                                        Panggil Antrian
                                                    </button>
                                                </form>
                                            <?php elseif(!$currentTicket && $stageTickets->isNotEmpty()): ?>
                                                <span class="text-xs text-slate-400 font-semibold whitespace-nowrap">Bukan tahap Anda</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($stageTickets->isEmpty()): ?>
                                            <div class="p-8 text-center text-slate-400 dark:text-slate-500 text-sm">Tidak ada antrian untuk tahap ini.</div>
                                        <?php else: ?>
                                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                                                <?php $__currentLoopData = $stageTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <?php $ticketLocation = $ticket->location; ?>
                                                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-xl p-4 border border-slate-200 dark:border-slate-600 hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
                                                        <div class="flex items-start justify-between gap-2 mb-2">
                                                            <span class="text-lg font-black text-slate-900 dark:text-white"><?php echo e($ticket->ticket_number); ?></span>
                                                            <?php if($ticket->status === 'waiting'): ?>
                                                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 whitespace-nowrap">Menunggu</span>
                                                            <?php elseif($ticket->status === 'calling'): ?>
                                                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 whitespace-nowrap">Dipanggil</span>
                                                            <?php elseif($ticket->status === 'serving'): ?>
                                                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 whitespace-nowrap">Sedang Dilayani</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate"><?php echo e($ticket->customer_name); ?></p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate"><?php echo e($ticket->customer_phone); ?></p>
                                                        <div class="flex items-center gap-1.5 flex-wrap mt-2">
                                                            <?php if($ticketLocation): ?>
                                                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300"><?php echo e($ticketLocation->name); ?></span>
                                                            <?php endif; ?>
                                                            <?php if($ticket->skip_count > 0): ?>
                                                                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Lewati <?php echo e($ticket->skip_count); ?>x</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if(in_array($ticket->status, ['waiting', 'calling'], true)): ?>
                                                            <div class="flex items-center gap-2 flex-wrap mt-3 pt-3 border-t border-slate-200 dark:border-slate-600">
                                                                <?php if(!$currentTicket && in_array($ticket->stage, $counter->stages ?? [], true)): ?>
                                                                    <form method="POST" action="<?php echo e(route('operator.call-specific', $ticket)); ?>" class="flex-1 min-w-[80px]"><?php echo csrf_field(); ?>
                                                                        <button type="submit" class="w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-1.5 rounded-lg text-xs font-semibold transition-colors">Panggil</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <?php if(in_array($ticket->stage, $counter->stages ?? [], true) && in_array($ticket->status, ['calling', 'serving'], true)): ?>
                                                                    <form method="POST" action="<?php echo e(route('operator.skip', $ticket)); ?>" class="flex-1 min-w-[80px]" onsubmit="return confirm('Lewati tiket <?php echo e($ticket->ticket_number); ?>?')"><?php echo csrf_field(); ?>
                                                                        <button type="submit" class="w-full bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-700 dark:text-slate-200 py-1.5 rounded-lg text-xs font-semibold transition-colors">Lewati</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <?php if(in_array($ticket->stage, $counter->stages ?? [], true)): ?>
                                                                    <a href="<?php echo e(route('operator.tickets.edit', $ticket)); ?>" class="p-2 bg-white dark:bg-slate-600 border border-slate-200 dark:border-slate-500 rounded-lg text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-500 transition-colors" title="Edit">
                                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                                    </a>
                                                                    <form method="POST" action="<?php echo e(route('operator.tickets.destroy', $ticket)); ?>" onsubmit="return confirm('Batalkan tiket <?php echo e($ticket->ticket_number); ?>?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                                        <button type="submit" class="p-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 text-red-600 rounded-lg transition-colors" title="Batalkan">
                                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                        </button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                        <?php if($counter && $counter->handlesStage('registration') && in_array('booking', $permissions)): ?>
                        <div id="tab-reservasi" class="tab-panel hidden space-y-4">
                        <!-- Reservasi Hari Ini -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Reservasi Hari Ini</h2>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5"><?php echo e($todayBookings->count()); ?> reservasi aktif</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 flex-shrink-0">Reservasi</span>
                            </div>

                            <?php if($todayBookings->isEmpty()): ?>
                                <div class="p-10 text-center text-slate-400 dark:text-slate-500 text-sm">Belum ada reservasi untuk hari ini.</div>
                            <?php else: ?>
                                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                    <?php $__currentLoopData = $todayBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            $statusPillColors = [
                                                'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                'confirmed' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                                'checked_in' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                                'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            ];
                                            $statusPillLabels = [
                                                'pending' => 'Menunggu Konfirmasi',
                                                'confirmed' => 'Akan datang',
                                                'checked_in' => 'Sudah Check-in',
                                                'cancelled' => 'Dibatalkan',
                                            ];
                                            $canAct = in_array($booking->status, ['pending', 'confirmed'], true);
                                            $isToday = $booking->booking_date->isToday();
                                            $timeLabel = is_string($booking->booking_time) ? $booking->booking_time : $booking->booking_time->format('H:i');
                                        ?>
                                        <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                            <!-- Time badge -->
                                            <div class="flex-shrink-0 bg-purple-600 text-white rounded-xl px-3.5 py-2 text-center leading-tight w-fit">
                                                <span class="block text-[11px] opacity-80"><?php echo e(\Illuminate\Support\Carbon::parse($booking->booking_date)->format('d/m')); ?></span>
                                                <span class="block text-sm font-bold"><?php echo e($timeLabel); ?></span>
                                            </div>

                                            <!-- Main info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300"><?php echo e($booking->location->code ?? '-'); ?></span>
                                                    <span class="font-bold text-slate-900 dark:text-white"><?php echo e($booking->customer_name); ?></span>
                                                    <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-semibold <?php echo e($statusPillColors[$booking->status] ?? 'bg-slate-100 text-slate-700'); ?>">
                                                        <?php echo e($statusPillLabels[$booking->status] ?? ucfirst($booking->status)); ?>

                                                    </span>
                                                </div>
                                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                                                    <?php echo e($booking->customer_phone); ?>

                                                    <span class="mx-1 text-slate-300 dark:text-slate-600">&middot;</span>
                                                    <?php if($booking->ticket): ?>
                                                        <a href="<?php echo e(route('tickets.show', $booking->ticket)); ?>" class="text-blue-600 dark:text-blue-400 hover:underline"><?php echo e($booking->booking_code); ?></a>
                                                    <?php else: ?>
                                                        <span class="font-medium text-slate-600 dark:text-slate-300"><?php echo e($booking->booking_code); ?></span>
                                                    <?php endif; ?>
                                                    <?php if($booking->serviceCategory): ?>
                                                        <span class="mx-1 text-slate-300 dark:text-slate-600">&middot;</span>
                                                        <?php echo e($booking->serviceCategory->name); ?>

                                                    <?php endif; ?>
                                                </p>
                                            </div>

                                            <!-- Actions -->
                                            <div class="flex items-center gap-2 flex-shrink-0 sm:justify-end">
                                                <?php if($canAct && $isToday): ?>
                                                    <form method="POST" action="<?php echo e(route('operator.bookings.call', $booking)); ?>" onsubmit="return confirm('Check-in dan panggil reservasi <?php echo e($booking->booking_code); ?>? Ini akan membuat tiket baru dan memanggil ke loket Anda.')">
                                                        <?php echo csrf_field(); ?>
                                                        <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">Check-in & Panggil</button>
                                                    </form>
                                                <?php elseif($canAct): ?>
                                                    <span class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap">Panggil saat hari H</span>
                                                <?php endif; ?>
                                                <?php if($canAct): ?>
                                                    <form method="POST" action="<?php echo e(route('operator.bookings.destroy', $booking)); ?>" onsubmit="return confirm('Batalkan reservasi <?php echo e($booking->booking_code); ?>?')">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-red-100 dark:bg-slate-700 dark:hover:bg-red-900/30 text-slate-600 hover:text-red-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">Batalkan</button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if($booking->ticket): ?>
                                                    <a href="<?php echo e(route('tickets.show', $booking->ticket)); ?>" class="px-3 py-1.5 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap">Lihat Tiket</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        </div><!-- /#tab-reservasi -->
                        <?php endif; ?>
                        </div><!-- /.md:col-span-2 -->

                    <!-- Sidebar -->
                    <div class="space-y-4 md:sticky md:top-20">
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm">Status Loket</h3>
                                <span class="text-xs text-slate-400">Langsung</span>
                            </div>
                            <div class="p-3">
                                <div class="space-y-2">
                                    <?php $__currentLoopData = $counters->groupBy('location.name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $locationName => $locationCounters): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="mb-2">
                                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1 truncate"><?php echo e($locationName); ?></p>
                                            <div class="space-y-1.5">
                                                <?php $__currentLoopData = $locationCounters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="flex items-center justify-between p-2.5 rounded-lg <?php echo e($c->currentOperator ? 'bg-green-50 dark:bg-green-900/20' : 'bg-slate-50 dark:bg-slate-700/50'); ?>">
                                                        <div class="min-w-0">
                                                            <span class="font-semibold text-slate-900 dark:text-white text-xs truncate block"><?php echo e($c->name); ?></span>
                                                            <span class="block text-xs text-blue-600 truncate"><?php echo e($c->stageLabel()); ?></span>
                                                            <?php if($c->currentOperator): ?>
                                                                <span class="block text-xs text-slate-500 dark:text-slate-400 truncate"><?php echo e($c->currentOperator->name); ?></span>
                                                            <?php else: ?>
                                                                <span class="block text-xs text-slate-400 dark:text-slate-500">Tidak aktif</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if($c->currentOperator): ?>
                                                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse flex-shrink-0 ml-2"></span>
                                                        <?php else: ?>
                                                            <span class="w-2 h-2 bg-slate-300 dark:bg-slate-600 rounded-full flex-shrink-0 ml-2"></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-4 border-b border-slate-100 dark:border-slate-700"><h3 class="font-bold text-slate-900 dark:text-white text-sm">Pintasan</h3></div>
                            <div class="p-3 space-y-2">
                                <a href="<?php echo e(route('locations.scan', $location)); ?>" target="_blank" class="flex items-center justify-between w-full bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                    <span>QR Order</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v2m0 14v2M4 12H2m20 0h-2m-2.5-6.5L17 7m-12 10l-1.5 1.5M17 17l1.5 1.5M7 7L5.5 5.5"/></svg>
                                </a>
                                <a href="<?php echo e(route('locations.display', $location)); ?>" target="_blank" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                    <span>Display</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 002 2v10a2 2 0 002 2z"/></svg>
                                </a>
                                <?php if(in_array('customers', $permissions)): ?>
                                    <button type="button" onclick="document.querySelector('[data-tab=&quot;tab-pelanggan&quot;]')?.click()" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                        <span>Data Pelanggan</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    </button>
                                <?php endif; ?>
                                <?php if(auth()->user()->isOperator() || auth()->user()->isAdmin()): ?>
                                    <a href="<?php echo e(route('operator.booking-slots')); ?>" class="flex items-center justify-between w-full border border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                        <span>Jadwal Reservasi</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2v12a2 2 0 002 2z"/></svg>
                                    </a>
                                <?php endif; ?>
                                <?php if(auth()->user()->isAdmin()): ?>
                                    <a href="<?php echo e(route('operator.choose-location')); ?>" class="flex items-center justify-between w-full border border-dashed border-slate-300 dark:border-slate-600 hover:border-slate-400 text-slate-500 dark:text-slate-400 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                                        <span>Ganti Lokasi (Admin)</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    </div>

                <!-- Data Pelanggan -->
                <?php if(in_array('customers', $permissions)): ?>
                    <div id="tab-pelanggan" class="tab-panel hidden mt-8 space-y-6">
                        <!-- Reservasi Section -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Reservasi</h3>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?php echo e($custBookings->count()); ?> reservasi ditemukan</p>
                                </div>
                                <?php if($canManageBookings): ?>
                                    <a href="<?php echo e(route('operator.bookings.create')); ?>" class="px-3 py-1.5 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-xs font-semibold transition-colors">Tambah Reservasi</a>
                                <?php endif; ?>
                            </div>
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <form method="GET" action="<?php echo e(route('operator.dashboard')); ?>#data-pelanggan" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                                        <input type="text" name="q" value="<?php echo e($custSearch); ?>" placeholder="Nama, telepon, email, atau no. reservasi..." class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                        <input type="date" name="date" value="<?php echo e($custDate); ?>" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                    </div>
                                    <div class="md:col-span-2 flex items-center gap-2">
                                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-sm font-semibold transition-colors">Terapkan Filter</button>
                                        <?php if($custSearch || $custDate !== now()->toDateString()): ?>
                                            <a href="<?php echo e(route('operator.dashboard')); ?>#data-pelanggan" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold transition-colors">Reset</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                            <?php if($custBookings->isEmpty()): ?>
                                <div class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">Tidak ada reservasi yang cocok dengan filter ini.</div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kode</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pelanggan</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kontak</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jadwal</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Layanan</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                            <?php $__currentLoopData = $custBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $statusClasses = [
                                                        'pending' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800',
                                                        'confirmed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800',
                                                        'checked_in' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
                                                        'cancelled' => 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
                                                    ];
                                                    $statusLabels = [
                                                        'pending' => 'Menunggu',
                                                        'confirmed' => 'Terkonfirmasi',
                                                        'checked_in' => 'Check-in',
                                                        'cancelled' => 'Dibatalkan',
                                                    ];
                                                ?>
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors">
                                                    <td class="px-5 py-3.5">
                                                        <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($booking->booking_code); ?></span>
                                                    </td>
                                                    <td class="px-5 py-3.5">
                                                        <div class="flex flex-col">
                                                            <span class="font-medium text-slate-900 dark:text-white"><?php echo e($booking->customer_name); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5">
                                                        <div class="flex flex-col text-xs">
                                                            <span class="text-slate-600 dark:text-slate-300"><?php echo e($booking->customer_phone); ?></span>
                                                            <span class="text-slate-500 dark:text-slate-400"><?php echo e($booking->customer_email ?? '-'); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5">
                                                        <div class="flex flex-col text-xs">
                                                            <span class="text-slate-600 dark:text-slate-300"><?php echo e($booking->booking_date->format('d M Y')); ?></span>
                                                            <span class="text-slate-500 dark:text-slate-400"><?php echo e(is_string($booking->booking_time) ? $booking->booking_time : $booking->booking_time->format('H:i')); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300"><?php echo e($booking->serviceCategory->name ?? '-'); ?></td>
                                                    <td class="px-5 py-3.5">
                                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold <?php echo e($statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200'); ?>">
                                                            <?php echo e($statusLabels[$booking->status] ?? ucfirst($booking->status)); ?>

                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-right">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <?php if($canManageBookings): ?>
                                                                <?php if(in_array($booking->status, ['pending', 'confirmed']) && $booking->booking_date->isToday()): ?>
                                                                    <form method="POST" action="<?php echo e(route('operator.bookings.call', $booking)); ?>" class="inline" onsubmit="return confirm('Check-in dan panggil reservasi <?php echo e($booking->booking_code); ?>?')"><?php echo csrf_field(); ?>
                                                                        <button type="submit" class="px-2.5 py-1.5 bg-gradient-to-r from-mint to-skyblue hover:opacity-90 text-white rounded-md text-xs font-semibold transition-colors">Panggil</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <?php if(in_array($booking->status, ['pending', 'confirmed'])): ?>
                                                                    <form method="POST" action="<?php echo e(route('operator.bookings.destroy', $booking)); ?>" class="inline" onsubmit="return confirm('Batalkan reservasi <?php echo e($booking->booking_code); ?>?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                                        <button type="submit" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold transition-colors">Batal</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            <?php if($booking->ticket): ?>
                                                                <a href="<?php echo e(route('tickets.show', $booking->ticket)); ?>" class="inline-block px-2.5 py-1.5 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-md text-xs font-semibold transition-colors">Lihat Tiket</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 px-6 pb-4">
                                    <?php echo e($custBookings->appends(['q' => $custSearch, 'date' => $custDate])->links()); ?>

                                </div>
                            <?php endif; ?>
                        </div>

                    <!-- Tiket Section -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Tiket</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?php echo e($custTickets->count()); ?> tiket ditemukan</p>
                            </div>
                            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                                <form method="GET" action="<?php echo e(route('operator.dashboard')); ?>#data-pelanggan" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                                        <input type="text" name="q" value="<?php echo e($custSearch); ?>" placeholder="Nama, telepon, email, atau no. tiket..." class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                                        <select name="status" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                            <option value="" <?php echo e($custStatus === '' ? 'selected' : ''); ?>>Semua Status</option>
                                            <option value="waiting" <?php echo e($custStatus === 'waiting' ? 'selected' : ''); ?>>Menunggu</option>
                                            <option value="calling" <?php echo e($custStatus === 'calling' ? 'selected' : ''); ?>>Dipanggil</option>
                                            <option value="serving" <?php echo e($custStatus === 'serving' ? 'selected' : ''); ?>>Sedang Dilayani</option>
                                            <option value="completed" <?php echo e($custStatus === 'completed' ? 'selected' : ''); ?>>Selesai</option>
                                            <option value="cancelled" <?php echo e($custStatus === 'cancelled' ? 'selected' : ''); ?>>Dibatalkan</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tahap</label>
                                        <select name="stage" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                            <option value="" <?php echo e($custStage === '' ? 'selected' : ''); ?>>Semua Tahap</option>
                                            <?php $__currentLoopData = \App\Models\Ticket::STAGE_ORDER; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($stage); ?>" <?php echo e($custStage === $stage ? 'selected' : ''); ?>><?php echo e(\App\Models\Ticket::STAGE_LABELS[$stage] ?? $stage); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                                        <input type="date" name="date" value="<?php echo e($custDate); ?>" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                    </div>
                                    <div class="md:col-span-5 flex items-center gap-2">
                                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-lg text-sm font-semibold transition-colors">Terapkan Filter</button>
                                        <?php if($custSearch || $custStatus || $custStage || $custDate !== now()->toDateString()): ?>
                                            <a href="<?php echo e(route('operator.dashboard')); ?>#data-pelanggan" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-semibold transition-colors">Reset</a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                            <?php if($custTickets->isEmpty()): ?>
                                <div class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">Tidak ada tiket yang cocok dengan filter ini.</div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-slate-200 dark:border-slate-700">
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">No. Antrian</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pelanggan</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kontak</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Lokasi</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tahap</th>
                                                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                                                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                            <?php $__currentLoopData = $custTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php
                                                    $ticketLocation = $ct->location;
                                                    $statusClasses = [
                                                        'waiting' => 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600',
                                                        'calling' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
                                                        'serving' => 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800',
                                                        'completed' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800',
                                                        'cancelled' => 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
                                                    ];
                                                    $statusLabels = [
                                                        'waiting' => 'Menunggu',
                                                        'calling' => 'Dipanggil',
                                                        'serving' => 'Dilayani',
                                                        'completed' => 'Selesai',
                                                        'cancelled' => 'Dibatalkan',
                                                    ];
                                                ?>
                                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors">
                                                    <td class="px-5 py-3.5">
                                                        <div class="flex flex-col">
                                                            <span class="font-semibold text-slate-900 dark:text-white"><?php echo e($ct->ticket_number); ?></span>
                                                            <?php if($ct->skip_count > 0): ?>
                                                                <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium mt-0.5">Lewati <?php echo e($ct->skip_count); ?>x</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5">
                                                        <span class="font-medium text-slate-900 dark:text-white"><?php echo e($ct->customer_name); ?></span>
                                                    </td>
                                                    <td class="px-5 py-3.5">
                                                        <div class="flex flex-col text-xs">
                                                            <span class="text-slate-600 dark:text-slate-300"><?php echo e($ct->customer_phone); ?></span>
                                                            <span class="text-slate-500 dark:text-slate-400"><?php echo e($ct->customer_email ?? '-'); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300">
                                                        <?php if($ticketLocation): ?>
                                                            <span class="inline-block px-2 py-1 rounded-md bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300"><?php echo e($ticketLocation->name); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-300"><?php echo e($ct->stageLabel()); ?></td>
                                                    <td class="px-5 py-3.5">
                                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-semibold <?php echo e($statusClasses[$ct->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200'); ?>">
                                                            <?php echo e($statusLabels[$ct->status] ?? ucfirst($ct->status)); ?>

                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-right">
                                                        <?php if(in_array($ct->status, ['waiting', 'calling'], true) && in_array($ct->stage, $counter->stages ?? [], true)): ?>
                                                            <div class="flex items-center justify-end gap-2">
                                                                <a href="<?php echo e(route('operator.tickets.edit', $ct)); ?>" class="inline-block px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-md text-xs font-semibold transition-colors">Edit</a>
                                                                <form method="POST" action="<?php echo e(route('operator.tickets.destroy', $ct)); ?>" class="inline" onsubmit="return confirm('Batalkan tiket <?php echo e($ct->ticket_number); ?>?')"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                                    <button type="submit" class="px-2.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold transition-colors">Hapus</button>
                                                                </form>
                                                            </div>
                                                        <?php elseif($ct->counter): ?>
                                                            <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo e($ct->counter->name); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 px-6 pb-4">
                                    <?php echo e($custTickets->appends(['q' => $custSearch, 'status' => $custStatus, 'stage' => $custStage, 'date' => $custDate])->links()); ?>

                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal23a33f287873b564aaf305a1526eada4)): ?>
<?php $attributes = $__attributesOriginal23a33f287873b564aaf305a1526eada4; ?>
<?php unset($__attributesOriginal23a33f287873b564aaf305a1526eada4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal23a33f287873b564aaf305a1526eada4)): ?>
<?php $component = $__componentOriginal23a33f287873b564aaf305a1526eada4; ?>
<?php unset($__componentOriginal23a33f287873b564aaf305a1526eada4); ?>
<?php endif; ?><?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/operator/dashboard.blade.php ENDPATH**/ ?>