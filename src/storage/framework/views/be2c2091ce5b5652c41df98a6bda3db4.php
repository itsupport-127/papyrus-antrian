
<?php
    $waktu = $waktuTicket?->stageDurations();
    // key segmen -> [relasi staf, label peran]. Nama staf cuma ditampilkan
    // kalau relasinya sudah di-eager-load (tabel Tiket) -- tabel lain
    // (mis. Reservasi) gak perlu N+1 query cuma buat nama staf.
    $wkStaffMap = [
        'registration' => ['registeredBy', 'CS'],
        'photo_session' => ['photographedBy', 'Fotografer'],
        'editing' => ['editedBy', 'Editor'],
        'payment_processing' => ['paidBy', 'Kasir'],
    ];
    $wkLabels = ['payment_processing' => 'Bayar'];
    $wkWaiting = ['photo_wait', 'payment_wait'];
?>
<?php if(!$waktuTicket || !$waktu): ?>
    <span class="text-slate-400 dark:text-slate-500">-</span>
<?php else: ?>
    <div class="flex items-baseline gap-1.5">
        <span class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-slate-500">Total</span>
        <span class="font-bold text-slate-900 dark:text-white text-base">
            <?php if($waktu['total']['ongoing']): ?>
                <span class="waktu-live" data-start="<?php echo e($waktu['total']['start']->timestamp * 1000); ?>"><?php echo e(\App\Models\Ticket::formatDuration($waktu['total']['seconds'])); ?></span>
            <?php else: ?>
                <?php echo e(\App\Models\Ticket::formatDuration($waktu['total']['seconds'])); ?>

            <?php endif; ?>
        </span>
    </div>
    <ul class="mt-2 space-y-1.5">
        <?php $__currentLoopData = $waktu['segments']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $isWait = in_array($s['key'], $wkWaiting, true);
                $staffUser = null;
                $staffRole = null;
                if (isset($wkStaffMap[$s['key']]) && $waktuTicket->relationLoaded($wkStaffMap[$s['key']][0])) {
                    $staffUser = $waktuTicket->{$wkStaffMap[$s['key']][0]};
                    $staffRole = $wkStaffMap[$s['key']][1];
                }
            ?>
            <li class="flex items-start justify-between gap-4 <?php echo e($isWait ? 'pl-3' : ''); ?>">
                <div class="leading-tight">
                    <span class="<?php echo e($isWait ? 'text-xs italic text-slate-400 dark:text-slate-500' : 'text-sm font-semibold text-slate-800 dark:text-slate-100'); ?>"><?php echo e($wkLabels[$s['key']] ?? $s['label']); ?></span>
                    <?php if($staffUser): ?>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400"><?php echo e($staffRole); ?>: <?php echo e($staffUser->name); ?></span>
                    <?php endif; ?>
                </div>
                <span class="whitespace-nowrap <?php echo e($isWait ? 'text-xs italic' : 'text-sm font-semibold'); ?> <?php echo e($s['ongoing'] ? 'text-blue-600 dark:text-blue-400' : ($isWait ? 'text-slate-400 dark:text-slate-500' : 'text-slate-800 dark:text-slate-100')); ?>">
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
    <?php if($waktuTicket->payment_called_at || $waktuTicket->payment_served_at || $waktuTicket->payment_completed_at): ?>
        <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-700">
            <div class="text-[10px] uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-1">Jam Pembayaran</div>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[11px]">
                <?php $__currentLoopData = [
                    'Selesai foto' => $waktuTicket->photo_completed_at,
                    'Dipanggil kasir' => $waktuTicket->payment_called_at,
                    'Mulai bayar' => $waktuTicket->payment_served_at,
                    'Selesai bayar' => $waktuTicket->payment_completed_at,
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lbl => $jam): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="leading-tight">
                        <span class="block text-slate-400 dark:text-slate-500"><?php echo e($lbl); ?></span>
                        <span class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e($jam?->format('H:i:s') ?? '-'); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/operator/partials/waktu-cell.blade.php ENDPATH**/ ?>