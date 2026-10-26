<div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
    <div class="papyrus-tape"></div>
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gradient-to-br from-lavender via-skyblue to-dustyrose rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="font-display font-bold text-lg text-slate-900 dark:text-white"><?php echo e($title); ?></h1>
                    <?php if($subtitle ?? null): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo e($subtitle); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white px-3 py-2 text-sm font-medium">
                    Dashboard
                </a>
                <a href="<?php echo e(route('home')); ?>" target="_blank" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white px-3 py-2 text-sm font-medium">
                    Lihat Situs
                </a>
            </div>
        </div>
    </div>
</div>
<?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/components/admin-header.blade.php ENDPATH**/ ?>