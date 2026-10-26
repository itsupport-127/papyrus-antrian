
<?php if (! $__env->hasRenderedOnce('fb18f81d-14db-4f62-b6ac-20a83c9becc5')): $__env->markAsRenderedOnce('fb18f81d-14db-4f62-b6ac-20a83c9becc5'); ?>
    <?php
        $selectingTickets = $selectingTickets ?? collect();
        $photoSelectMinutes = $photoSelectMinutes ?? 15;
        $canSetSelectTimer = auth()->user()->isOperator() || auth()->user()->isAdmin();
    ?>
    <?php if($selectingTickets->isNotEmpty() || $canSetSelectTimer): ?>
        <div id="photo-selection-panel" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden mb-6">
            <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Pilih Foto <span class="text-xs font-semibold text-violet-600 dark:text-violet-400 align-middle">Khusus Reservasi</span></h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5"><?php echo e($selectingTickets->count()); ?> tiket di tahap Pilih Foto</p>
                </div>
                <?php if($canSetSelectTimer): ?>
                    <form method="POST" action="<?php echo e(route('operator.photo-select-setting')); ?>" class="flex items-center gap-2">
                        <?php echo csrf_field(); ?>
                        <label for="photo_select_minutes" class="text-xs font-medium text-slate-600 dark:text-slate-300">Durasi Pilih Foto</label>
                        <input type="number" id="photo_select_minutes" name="photo_select_minutes" min="1" max="120" value="<?php echo e($photoSelectMinutes); ?>" class="w-20 px-2 py-1.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white text-sm">
                        <span class="text-xs text-slate-500 dark:text-slate-400">menit</span>
                        <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-semibold">Simpan</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="p-4">
                <?php if($selectingTickets->isEmpty()): ?>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada tiket reservasi di tahap Pilih Foto. Durasi di atas berlaku untuk tiket berikutnya.</p>
                <?php else: ?>
                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                        <?php $__currentLoopData = $selectingTickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $state = $st->photoSelectionState(); ?>
                            <div class="py-3 flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-lg font-bold text-slate-900 dark:text-white"><?php echo e($st->ticket_number); ?></p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300 truncate"><?php echo e($st->customer_name ?: '-'); ?></p>
                                    <?php if($st->booking?->order_code): ?>
                                        <p class="font-mono text-xs text-slate-400"><?php echo e($st->booking->order_code); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-center">
                                    <?php if($state === 'selecting'): ?>
                                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Sisa waktu</p>
                                        <p class="font-mono text-2xl font-bold text-violet-700 dark:text-violet-300" data-select-end="<?php echo e($st->select_ends_at?->getTimestamp()); ?>">--:--</p>
                                    <?php elseif($state === 'called'): ?>
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Sudah dipanggil</span>
                                    <?php else: ?>
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Menunggu dipanggil</span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <?php if($state === 'waiting' || $state === 'called'): ?>
                                        <form method="POST" action="<?php echo e(route('operator.call-photo-selection', $st)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="px-3 py-1.5 <?php echo e($state === 'waiting' ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'border border-amber-500 text-amber-700 dark:text-amber-400'); ?> rounded-lg text-xs font-semibold"><?php echo e($state === 'waiting' ? 'Panggil Pilih Foto' : 'Panggil Ulang'); ?></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if($state === 'called'): ?>
                                        <form method="POST" action="<?php echo e(route('operator.begin-photo-selection', $st)); ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-semibold">Mulai Pilih Foto</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if($state === 'selecting'): ?>
                                        <?php $__currentLoopData = [5, 10]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addMin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <form method="POST" action="<?php echo e(route('operator.extend-photo-selection', $st)); ?>">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="minutes" value="<?php echo e($addMin); ?>">
                                                <button type="submit" class="px-2.5 py-1.5 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-lg text-xs font-semibold">+<?php echo e($addMin); ?> menit</button>
                                            </form>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <form method="POST" action="<?php echo e(route('operator.finish-photo-selection', $st)); ?>" onsubmit="return confirmFormSubmit(this, { message: 'Selesai Pilih Foto untuk <?php echo e($st->ticket_number); ?>? Tiket lanjut ke Pembayaran, hasil edit menyusul.', variant: 'primary', confirmText: 'Ya, ke Pembayaran' })">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-xs font-semibold">Selesai Pilih Foto</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <script>
            (function () {
                if (window.__papyrusSelectTimer) return;
                window.__papyrusSelectTimer = setInterval(function () {
                    document.querySelectorAll('[data-select-end]').forEach(function (el) {
                        var end = parseInt(el.getAttribute('data-select-end'), 10);
                        if (!end) return;
                        var left = end - Math.floor(Date.now() / 1000);
                        if (left <= 0) {
                            el.textContent = 'Waktu habis';
                            el.classList.add('text-red-600');
                            return;
                        }
                        var m = Math.floor(left / 60), s = left % 60;
                        el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    });
                }, 1000);
            })();
        </script>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/operator/partials/photo-selection-panel.blade.php ENDPATH**/ ?>