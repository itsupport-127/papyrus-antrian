<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Data Pelanggan']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Data Pelanggan']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <?php if (isset($component)) { $__componentOriginalf4be4a995e439509ee9419c9da1d5b3d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf4be4a995e439509ee9419c9da1d5b3d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.operator-header','data' => ['title' => 'Data Pelanggan','subtitle' => ''.e($location->name).' — semua tiket & status terkininya']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('operator-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Data Pelanggan','subtitle' => ''.e($location->name).' — semua tiket & status terkininya']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf4be4a995e439509ee9419c9da1d5b3d)): ?>
<?php $attributes = $__attributesOriginalf4be4a995e439509ee9419c9da1d5b3d; ?>
<?php unset($__attributesOriginalf4be4a995e439509ee9419c9da1d5b3d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf4be4a995e439509ee9419c9da1d5b3d)): ?>
<?php $component = $__componentOriginalf4be4a995e439509ee9419c9da1d5b3d; ?>
<?php unset($__componentOriginalf4be4a995e439509ee9419c9da1d5b3d); ?>
<?php endif; ?>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-6">
                <form method="GET" action="<?php echo e(route('operator.customers')); ?>" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari</label>
                        <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="Nama, no. telepon, atau no. tiket..."
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
                        <select name="status" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <option value="" <?php echo e($status === '' ? 'selected' : ''); ?>>Semua Status</option>
                            <option value="waiting" <?php echo e($status === 'waiting' ? 'selected' : ''); ?>>Menunggu</option>
                            <option value="calling" <?php echo e($status === 'calling' ? 'selected' : ''); ?>>Dipanggil</option>
                            <option value="serving" <?php echo e($status === 'serving' ? 'selected' : ''); ?>>Sedang Dilayani</option>
                            <option value="editing" <?php echo e($status === 'editing' ? 'selected' : ''); ?>>Sedang Diedit</option>
                            <option value="completed" <?php echo e($status === 'completed' ? 'selected' : ''); ?>>Selesai</option>
                            <option value="cancelled" <?php echo e($status === 'cancelled' ? 'selected' : ''); ?>>Dibatalkan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tahap</label>
                        <select name="stage" class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <option value="" <?php echo e($stage === '' ? 'selected' : ''); ?>>Semua Tahap</option>
                            <option value="registration" <?php echo e($stage === 'registration' ? 'selected' : ''); ?>>Registrasi</option>
                            <option value="photo_session" <?php echo e($stage === 'photo_session' ? 'selected' : ''); ?>>Sesi Foto</option>
                            <option value="payment" <?php echo e($stage === 'payment' ? 'selected' : ''); ?>>Pembayaran & Cetak</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal</label>
                        <input type="date" name="date" value="<?php echo e($date === 'all' ? '' : $date); ?>"
                            class="w-full border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                    </div>
                    <div class="md:col-span-5 flex items-center gap-3">
                        <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                            Terapkan Filter
                        </button>
                        <a href="<?php echo e(route('operator.customers', ['date' => 'all'])); ?>" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white">
                            Lihat semua tanggal
                        </a>
                        <?php if($search || $status || $stage || $date !== now()->toDateString()): ?>
                            <a href="<?php echo e(route('operator.customers')); ?>" class="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white">
                                Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 dark:bg-slate-900/40">
                            <tr>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tiket</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Pelanggan</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Email</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tahap</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Status</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Dilayani Oleh</th>
                                <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="operator-customers-table-body" class="divide-y divide-slate-200 dark:divide-slate-700">
                            <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-purple-700 dark:text-purple-400"><?php echo e($ticket->ticket_number); ?></span>
                                        <?php if($ticket->skip_count > 0): ?>
                                            <span class="block text-xs text-orange-500 mt-0.5">Dilewati <?php echo e($ticket->skip_count); ?>x</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-slate-900 dark:text-white"><?php echo e($ticket->customer_name ?? '-'); ?></span>
                                        <span class="block text-sm text-slate-500 dark:text-slate-400"><?php echo e($ticket->customer_phone ?? '-'); ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 text-sm"><?php echo e($ticket->customer_email ?? '-'); ?></td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 text-sm">
                                        <?php echo e($ticket->stageLabel()); ?>

                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                            $statusMap = [
                                                'waiting' => ['label' => 'Menunggu', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'],
                                                'calling' => ['label' => 'Dipanggil', 'class' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                                                'serving' => ['label' => 'Sedang Dilayani', 'class' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
                                                'editing' => ['label' => 'Sedang Diedit', 'class' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'],
                                                'completed' => ['label' => 'Selesai', 'class' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                                                'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
                                            ];
                                            $badge = $statusMap[$ticket->status] ?? ['label' => $ticket->status, 'class' => 'bg-slate-100 text-slate-700'];
                                        ?>
                                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full <?php echo e($badge['class']); ?>">
                                            <?php echo e($badge['label']); ?>

                                        </span>
                                        
                                        <?php if($ticket->isEditPending()): ?>
                                            <span class="block mt-1 text-xs text-amber-600 dark:text-amber-400"><?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'clock']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clock']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?> Edit menyusul</span>
                                        <?php elseif($ticket->isReadyForPickup()): ?>
                                            <span class="block mt-1 text-xs text-sky-600 dark:text-sky-400"><?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'archive-box']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'archive-box']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?> Siap diambil (<?php echo e($ticket->order_code); ?>)</span>
                                            <form method="POST" action="<?php echo e(route('operator.mark-picked-up', $ticket)); ?>" class="mt-1" onsubmit="return confirmFormSubmit(this, { message: 'Tandai tiket ' + '<?php echo e($ticket->ticket_number); ?>' + ' (' + '<?php echo e($ticket->order_code); ?>' + ') sudah diambil pelanggan?', variant: 'primary', confirmText: 'Ya, Sudah Diambil' })">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-semibold bg-sky-600 hover:bg-sky-700 text-white">
                                                    Sudah Diambil
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 dark:text-slate-300 text-sm">
                                        <?php echo e($ticket->servedBy?->name ?? '-'); ?>

                                        <?php if($ticket->counter): ?>
                                            <span class="block text-xs text-slate-400"><?php echo e($ticket->counter->name); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm min-w-[180px]">
                                        <?php $waktu = $ticket->stageDurations(); ?>
                                        <div class="font-bold text-slate-900 dark:text-white text-sm">
                                            <?php if($waktu['total']['ongoing']): ?>
                                                <span class="waktu-live" data-start="<?php echo e($waktu['total']['start']->timestamp * 1000); ?>"><?php echo e(\App\Models\Ticket::formatDuration($waktu['total']['seconds'])); ?></span>
                                            <?php else: ?>
                                                <?php echo e(\App\Models\Ticket::formatDuration($waktu['total']['seconds'])); ?>

                                            <?php endif; ?>
                                        </div>
                                        <ul class="mt-1 space-y-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            <?php $__currentLoopData = $waktu['segments']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <li class="flex items-center justify-between gap-3">
                                                    <span><?php echo e($s['label']); ?></span>
                                                    <span class="font-medium <?php echo e($s['ongoing'] ? 'text-blue-600 dark:text-blue-400' : 'text-slate-600 dark:text-slate-300'); ?>">
                                                        <?php if(!$s['available']): ?>
                                                            -
                                                        <?php elseif($s['ongoing']): ?>
                                                            <span class="waktu-live" data-start="<?php echo e($s['start']->timestamp * 1000); ?>"><?php echo e(\App\Models\Ticket::formatDuration($s['seconds'])); ?></span>
                                                        <?php else: ?>
                                                            <?php echo e(\App\Models\Ticket::formatDuration($s['seconds'])); ?>

                                                        <?php endif; ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </ul>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                        Tidak ada tiket yang cocok dengan filter ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <?php echo e($tickets->links()); ?>

            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        // Tabel ikut update otomatis; form filter di atas tidak disentuh
        // sama sekali jadi pilihan/ketikan tidak ke-reset.
        window.autoRefreshIds(['operator-customers-table-body'], 6000);

        // Widget "WAKTU": angka buat tahap yang MASIH BERJALAN harus jalan
        // hidup per detik (bukan cuma ke-update tiap 6 detik bareng
        // autoRefreshIds di atas). Query ulang tiap tick (bukan cache
        // referensi elemen) karena autoRefreshIds mengganti innerHTML
        // tbody-nya secara berkala.
        function formatDurationJs(totalSeconds) {
            totalSeconds = Math.max(0, Math.floor(totalSeconds));
            if (totalSeconds < 60) return totalSeconds + 's';
            const minutes = Math.floor(totalSeconds / 60);
            if (minutes < 60) return minutes + 'm ' + (totalSeconds % 60) + 's';
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + 'h ' + (minutes % 60) + 'm';
            const days = Math.floor(hours / 24);
            return days + 'd ' + (hours % 24) + 'h';
        }
        setInterval(() => {
            document.querySelectorAll('.waktu-live').forEach((el) => {
                const start = parseInt(el.dataset.start, 10);
                if (!start) return;
                el.textContent = formatDurationJs((Date.now() - start) / 1000);
            });
        }, 1000);
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
<?php endif; ?><?php /**PATH /var/www/html/resources/views/operator/customers.blade.php ENDPATH**/ ?>