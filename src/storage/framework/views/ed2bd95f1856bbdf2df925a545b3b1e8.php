
<?php
    $dlStaff = collect($dilayaniTicket->stageStaff())->filter(fn ($s) => $s['user']);
    $dlActive = in_array($dilayaniTicket->status, ['calling', 'serving', 'editing', 'preparing'], true) && $dilayaniTicket->servedBy;
?>
<?php if($dlStaff->isEmpty() && !$dlActive): ?>
    <span class="text-slate-400 dark:text-slate-500">-</span>
<?php else: ?>
    <?php if($dlActive): ?>
        <div class="mb-2 px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
            <span class="text-[10px] uppercase tracking-wide text-blue-500 dark:text-blue-400">Sedang dipegang</span>
            <span class="block text-sm font-semibold text-blue-700 dark:text-blue-300"><?php echo e($dilayaniTicket->servedBy->name); ?></span>
            <?php if($dilayaniTicket->counter): ?>
                <span class="block text-[11px] text-blue-500 dark:text-blue-400"><?php echo e($dilayaniTicket->counter->name); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <ul class="space-y-1.5">
        <?php $__currentLoopData = $dlStaff; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="leading-tight">
                <span class="block text-[11px] text-slate-400 dark:text-slate-500"><?php echo e($s['label']); ?></span>
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-100"><?php echo e($s['user']->name); ?></span>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/operator/partials/dilayani-cell.blade.php ENDPATH**/ ?>