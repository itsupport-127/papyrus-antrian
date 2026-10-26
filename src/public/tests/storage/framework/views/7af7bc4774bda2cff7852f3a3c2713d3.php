<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Laporan & Analitik']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Laporan & Analitik']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <!-- Admin Header -->
        <div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-lavender via-skyblue to-dustyrose rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-slate-900 dark:text-white">Laporan & Analitik</h1>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Statistik dan performa antrian</p>
                        </div>
                    </div>
                    <a href="<?php echo e(route('admin.dashboard')); ?>" class="text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-3 py-2 text-sm font-medium">
                        ← Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                <form method="GET" action="<?php echo e(route('admin.reports')); ?>" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Periode</label>
                        <select name="period" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <option value="today" <?php echo e($period === 'today' ? 'selected' : ''); ?>>Hari Ini</option>
                            <option value="week" <?php echo e($period === 'week' ? 'selected' : ''); ?>>Minggu Ini</option>
                            <option value="month" <?php echo e($period === 'month' ? 'selected' : ''); ?>>Bulan Ini</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lokasi</label>
                        <select name="location_id" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            <option value="">Semua Lokasi</option>
                            <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($loc->id); ?>" <?php echo e($locationId == $loc->id ? 'selected' : ''); ?>>
                                    <?php echo e($loc->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                        Filter
                    </button>
                </form>
            </div>

            <div id="reports-content">
            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total Tiket</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e($stats['total_tickets']); ?></p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Selesai</p>
                    <p class="text-3xl font-bold text-green-600 mt-1"><?php echo e($stats['completed']); ?></p>
                    <?php if($stats['total_tickets'] > 0): ?>
                        <p class="text-sm text-slate-400 mt-1"><?php echo e(round($stats['completed'] / $stats['total_tickets'] * 100)); ?>%</p>
                    <?php endif; ?>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Rata-rata Layanan</p>
                    <p class="text-3xl font-bold text-blue-600 mt-1"><?php echo e($stats['avg_service_time']); ?></p>
                    <p class="text-sm text-slate-400 mt-1">menit</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Rata-rata Tunggu</p>
                    <p class="text-3xl font-bold text-amber-600 mt-1"><?php echo e($stats['avg_wait_time']); ?></p>
                    <p class="text-sm text-slate-400 mt-1">menit</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-8 mb-8">
                <!-- Ticket Status Breakdown -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Status Tiket</h2>
                    <div class="space-y-3">
                        <?php
                            $statuses = [
                                ['label' => 'Menunggu', 'count' => $stats['waiting'], 'color' => 'bg-amber-500'],
                                ['label' => 'Selesai', 'count' => $stats['completed'], 'color' => 'bg-green-500'],
                                ['label' => 'Dilewati', 'count' => $stats['skipped'], 'color' => 'bg-orange-500'],
                                ['label' => 'Dibatalkan', 'count' => $stats['cancelled'], 'color' => 'bg-red-500'],
                            ];
                        ?>
                        <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full <?php echo e($st['color']); ?>"></div>
                                    <span class="text-sm text-slate-700 dark:text-slate-300"><?php echo e($st['label']); ?></span>
                                </div>
                                <span class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo e($st['count']); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <!-- Feedback Summary -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Feedback Pelanggan</h2>
                    <div class="text-center py-4">
                        <p class="text-4xl font-bold text-amber-500"><?php echo e($feedbackStats['avg_rating'] > 0 ? $feedbackStats['avg_rating'] : '-'); ?></p>
                        <div class="flex items-center justify-center mt-2 space-x-1">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-5 h-5 <?php echo e($i <= round($feedbackStats['avg_rating']) ? 'text-amber-400' : 'text-slate-300 dark:text-slate-600'); ?>" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            <?php endfor; ?>
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2"><?php echo e($feedbackStats['total']); ?> feedback diterima</p>
                    </div>
                </div>
            </div>

            <!-- Hourly Distribution -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Distribusi Per Jam</h2>
                <div class="flex items-end space-x-1 h-40">
                    <?php $maxHourly = max($hourlyData) ?: 1; ?>
                    <?php $__currentLoopData = $hourlyData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hour => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($hour >= 7 && $hour <= 18): ?>
                            <div class="flex-1 flex flex-col items-center group">
                                <span class="text-xs text-slate-500 dark:text-slate-400 mb-1 opacity-0 group-hover:opacity-100 transition-opacity"><?php echo e($count); ?></span>
                                <div
                                    class="w-full bg-blue-500 rounded-t-sm transition-all hover:bg-blue-600"
                                    style="height: <?php echo e(($count / $maxHourly) * 100); ?>%"
                                    title="<?php echo e(str_pad($hour, 2, '0', STR_PAD_LEFT)); ?>:00 - <?php echo e($count); ?> tiket"
                                ></div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo e(str_pad($hour, 2, '0', STR_PAD_LEFT)); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-8">
                <!-- Per Location Breakdown -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Per Lokasi</h2>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $locationStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-xl">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white text-sm"><?php echo e($loc->name); ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e($loc->code); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-slate-900 dark:text-white text-sm"><?php echo e($loc->total_tickets); ?></p>
                                    <p class="text-xs text-green-600"><?php echo e($loc->completed_tickets); ?> selesai</p>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php if($locationStats->isEmpty()): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum ada data.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Operator Performance -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Performa Operator</h2>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $operatorStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-700 rounded-xl">
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white text-sm"><?php echo e($op->name); ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e($op->location?->name ?? '-'); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-slate-900 dark:text-white text-sm"><?php echo e($op->tickets_served); ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">tiket dilayani</p>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php if($operatorStats->isEmpty()): ?>
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum ada data.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        window.autoRefreshIds(['reports-content'], 10000);
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
<?php /**PATH D:\IT Support\Code\Antrian-Papyrus-Photo\resources\views/admin/reports/index.blade.php ENDPATH**/ ?>