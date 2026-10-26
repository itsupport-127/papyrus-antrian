
<?php if($ticket->isReservation() && $ticket->payment_skipped_pending_edit_at && !$ticket->edit_completed_at): ?>
    <?php
        $etaLate = $ticket->edit_eta_date && $ticket->edit_eta_date->lt(today());
    ?>
    <div class="mt-2 text-left">
        <p class="text-xs">
            <?php if($ticket->edit_eta_date): ?>
                Estimasi: <span class="font-semibold"><?php echo e($ticket->edit_eta_date->isToday() ? 'hari ini' : $ticket->edit_eta_date->translatedFormat('d M')); ?></span>
                <?php if($etaLate): ?>
                    <span class="inline-flex px-1.5 py-0.5 rounded bg-red-100 text-red-700 text-[10px] font-semibold ml-1">Terlambat <?php echo e($ticket->edit_eta_date->diffInDays(today())); ?> hari</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="text-slate-400">Estimasi belum diisi</span>
            <?php endif; ?>
            <?php if($ticket->edit_note): ?>
                <span class="block text-slate-500 dark:text-slate-400"><?php echo e($ticket->edit_note); ?></span>
            <?php endif; ?>
        </p>
        <form method="POST" action="<?php echo e(route('operator.edit-progress', $ticket)); ?>" class="mt-1 flex flex-wrap items-center gap-1.5">
            <?php echo csrf_field(); ?>
            <input type="date" name="edit_eta_date" value="<?php echo e($ticket->edit_eta_date?->toDateString()); ?>" class="px-2 py-1 border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-700 text-xs">
            <input type="text" name="edit_note" value="<?php echo e($ticket->edit_note); ?>" maxlength="255" placeholder="Catatan progres (mis. sedang retouch)" class="px-2 py-1 border border-slate-300 dark:border-slate-600 rounded-md bg-white dark:bg-slate-700 text-xs w-56">
            <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200">Simpan</button>
        </form>
    </div>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/operator/partials/edit-progress.blade.php ENDPATH**/ ?>