

<?php $part = $part ?? 'all'; ?>
<?php if($part !== 'rest'): ?>
    <span class="block font-mono text-xs font-normal text-slate-400 dark:text-slate-500 mt-0.5"><?php echo e($ticket->order_code); ?></span>
    <?php if($withName ?? false): ?>
        <span class="block text-base font-bold leading-tight break-words mt-0.5"><?php echo e($ticket->customer_name); ?></span>
    <?php endif; ?>
<?php endif; ?>
<?php if($part !== 'head'): ?>
<?php if($ticket->customer_phone || $ticket->customer_email): ?>
    <span class="block text-xs font-normal text-slate-500 dark:text-slate-400 mt-0.5"><?php echo e($ticket->customer_phone); ?><?php if($ticket->customer_phone && $ticket->customer_email): ?> &middot; <?php endif; ?><?php echo e($ticket->customer_email); ?></span>
<?php endif; ?>
<?php if($ticket->notes): ?>
    
    <?php echo $__env->make('operator.partials.order-notes', ['notes' => $ticket->notes, 'tone' => 'plain'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php else: ?>
    <span class="block text-xs font-normal text-slate-400 dark:text-slate-500 mt-0.5">Order: -</span>
<?php endif; ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/operator/partials/ticket-detail.blade.php ENDPATH**/ ?>