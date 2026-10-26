
<?php
    $tvLbl = $ticket->tvLabel();
    $tvMode = $mode ?? 'stack';
?>
<?php if($tvLbl): ?>
    <?php if($tvMode === 'inline'): ?>
        
        <span class="inline-flex flex-wrap items-baseline gap-x-1.5 min-w-0 leading-tight"><span class="break-words"><?php echo e($tvLbl['name']); ?></span><?php if($tvLbl['code']): ?><span class="font-mono opacity-80" style="font-size:.88em"><?php echo e($tvLbl['code']); ?></span><?php endif; ?></span>
    <?php else: ?>
        <span class="block leading-tight break-words" style="font-size:.72em"><?php echo e($tvLbl['name']); ?></span>
        <?php if($tvLbl['code']): ?>
            <span class="block font-mono leading-tight opacity-80" style="font-size:.5em"><?php echo e($tvLbl['code']); ?></span>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <?php echo e($ticket->ticket_number); ?>

<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/locations/partials/tv-ticket-label.blade.php ENDPATH**/ ?>