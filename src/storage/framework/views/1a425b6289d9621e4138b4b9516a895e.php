<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Riwayat Kerja - ' . $staff->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Riwayat Kerja - ' . $staff->name)]); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <?php if (isset($component)) { $__componentOriginal489711a049975b0fbcd3875ea3652a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal489711a049975b0fbcd3875ea3652a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-header','data' => ['title' => 'Laporan & Analitik','subtitle' => 'Riwayat kerja per staf']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Laporan & Analitik','subtitle' => 'Riwayat kerja per staf']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal489711a049975b0fbcd3875ea3652a04)): ?>
<?php $attributes = $__attributesOriginal489711a049975b0fbcd3875ea3652a04; ?>
<?php unset($__attributesOriginal489711a049975b0fbcd3875ea3652a04); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal489711a049975b0fbcd3875ea3652a04)): ?>
<?php $component = $__componentOriginal489711a049975b0fbcd3875ea3652a04; ?>
<?php unset($__componentOriginal489711a049975b0fbcd3875ea3652a04); ?>
<?php endif; ?>

        <div class="w-full px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-6 items-start">
            <?php if (isset($component)) { $__componentOriginal6fc2d165f80d597f34aa0f8014c366d2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2)): ?>
<?php $attributes = $__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2; ?>
<?php unset($__attributesOriginal6fc2d165f80d597f34aa0f8014c366d2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6fc2d165f80d597f34aa0f8014c366d2)): ?>
<?php $component = $__componentOriginal6fc2d165f80d597f34aa0f8014c366d2; ?>
<?php unset($__componentOriginal6fc2d165f80d597f34aa0f8014c366d2); ?>
<?php endif; ?>
            <div class="flex-1 min-w-0 w-full max-w-7xl mx-auto">

                <!-- Header staf + filter -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                    <a href="<?php echo e(route('admin.reports', array_filter(['period' => in_array($period, ['today', 'week', 'month'], true) ? $period : null, 'location_id' => $locationId]))); ?>"
                       class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 mb-3">
                        &larr; Kembali ke Laporan
                    </a>
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white"><?php echo e($staff->name); ?></h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                <?php echo e(ucfirst($staff->role)); ?><?php if($staff->location): ?> &middot; <?php echo e($staff->location->name); ?><?php endif; ?>
                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Periode: <span class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e($periodLabel); ?></span></p>
                        </div>
                        <form method="GET" action="<?php echo e(route('admin.reports.staff', $staff)); ?>" class="flex flex-wrap gap-4 items-end" x-data="{ period: '<?php echo e($period); ?>' }">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Periode</label>
                                <select name="period" x-model="period" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                    <option value="all">Semua Waktu</option>
                                    <option value="today">Hari Ini</option>
                                    <option value="week">Minggu Ini</option>
                                    <option value="month">Bulan Ini</option>
                                    <option value="year">Tahun Ini</option>
                                    <option value="custom">Custom...</option>
                                </select>
                            </div>
                            <div x-show="period === 'custom'" x-cloak class="flex gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Dari</label>
                                    <input type="date" name="from" value="<?php echo e($from); ?>" :disabled="period !== 'custom'" class="border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sampai</label>
                                    <input type="date" name="to" value="<?php echo e($to); ?>" :disabled="period !== 'custom'" class="border border-slate-300 dark:border-slate-600 rounded-lg px-3 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Lokasi</label>
                                <select name="location_id" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                                    <option value="">Semua Lokasi</option>
                                    <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $loc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($loc->id); ?>" <?php echo e($locationId == $loc->id ? 'selected' : ''); ?>><?php echo e($loc->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cari tiket / pelanggan</label>
                                <input type="text" name="q" value="<?php echo e($search); ?>" placeholder="No. tiket, nama, telepon" style="width: 13rem;" class="border border-slate-300 dark:border-slate-600 rounded-lg px-4 py-2 bg-white dark:bg-slate-700 dark:text-white text-sm">
                            </div>
                            <button type="submit" class="bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                                Terapkan
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Ringkasan -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tiket Dipegang</p>
                        <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e($tickets_count); ?></p>
                        <p class="text-xs text-slate-400 mt-1"><?php echo e($completed_count); ?> selesai</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Pelanggan Dilayani</p>
                        <p class="text-3xl font-bold text-blue-600 mt-1"><?php echo e($customers_served); ?></p>
                        <p class="text-xs text-slate-400 mt-1">pelanggan berbeda</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Pertama Kerja</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-2"><?php echo e($first_activity ? $first_activity->format('d/m/Y') : '-'); ?></p>
                        <p class="text-xs text-slate-400 mt-1"><?php echo e($first_activity ? $first_activity->format('H:i') : ''); ?></p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Terakhir Kerja</p>
                        <p class="text-xl font-bold text-slate-900 dark:text-white mt-2"><?php echo e($last_activity ? $last_activity->format('d/m/Y') : '-'); ?></p>
                        <p class="text-xs text-slate-400 mt-1"><?php echo e($last_activity ? $last_activity->format('H:i') : ''); ?></p>
                    </div>
                </div>

                <!-- Rekor pribadi & peringkat -->
                <?php $staffQuery = array_filter(['period' => $period !== 'all' ? $period : null, 'location_id' => $locationId, 'from' => $period === 'custom' ? $from : null, 'to' => $period === 'custom' ? $to : null]); ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1"><?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'trophy','size' => '1.15em','color' => '#d4a017']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trophy','size' => '1.15em','color' => '#d4a017']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?> Rekor &amp; Peringkat</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Rekor = durasi satu tahap tercepat sepanjang waktu (semua lokasi, tidak ikut filter periode). Cuma tiket &quot;Selesai&quot; dengan durasi minimal 5 detik yang dihitung. &quot;Pecah rekor&quot; = lebih cepat dari rekor pribadinya sebelumnya. Peringkat membandingkan rekor terbaik semua staf di tahap yang sama.</p>

                    <?php if(empty($records)): ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">Belum ada tiket selesai yang tercatat atas nama <?php echo e($staff->name); ?>, jadi belum ada rekor.</p>
                    <?php else: ?>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <?php $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $medalColor = ['#d4a017', '#94a3b8', '#b4763a'][($rec['rank'] ?? 0) - 1] ?? null;
                                    $diffLast = $rec['last_seconds'] - $rec['best_seconds'];
                                    $board = collect($rec['board']);
                                    $shown = $board->take(5);
                                    if ($rec['rank'] && !$shown->contains('user_id', $staff->id)) {
                                        $shown = $shown->push($board->firstWhere('user_id', $staff->id));
                                    }
                                ?>
                                <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase"><?php echo e($rec['label']); ?> &middot; <?php echo e($rec['role']); ?></p>
                                            <p class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e(\App\Models\Ticket::formatDuration($rec['best_seconds'])); ?></p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Rekor pribadi &middot; <?php echo e($rec['best_ticket']); ?> &middot; <?php echo e($rec['best_at']->format('d/m/Y H:i')); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <?php if($rec['rank']): ?>
                                                <p class="text-2xl leading-none"><?php if($medalColor): ?><?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'trophy','size' => '1.6em','color' => $medalColor]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trophy','size' => '1.6em','color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($medalColor)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?><?php endif; ?></p>
                                                <p class="text-sm font-bold text-slate-900 dark:text-white mt-1">Peringkat #<?php echo e($rec['rank']); ?></p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">dari <?php echo e($rec['total_staff']); ?> staf</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mt-3 space-y-1 text-sm">
                                        <p class="text-slate-700 dark:text-slate-300">
                                            <?php if($rec['break_count'] > 0): ?>
                                                <?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'arrow-trending-up','color' => '#16a34a']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-trending-up','color' => '#16a34a']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?> Pecah rekor pribadi <span class="font-semibold"><?php echo e($rec['break_count']); ?>×</span>
                                                <?php if($rec['break_in_period'] > 0): ?>
                                                    (<span class="font-semibold text-green-600"><?php echo e($rec['break_in_period']); ?>× di periode ini</span>)
                                                <?php else: ?>
                                                    (belum di periode ini)
                                                <?php endif; ?>
                                                <span class="block text-xs text-slate-500 dark:text-slate-400">Terakhir <?php echo e($rec['last_break_at']->format('d/m/Y H:i')); ?></span>
                                            <?php else: ?>
                                                Belum pernah memecahkan rekor pribadinya (<?php echo e($rec['results_count']); ?> tiket tercatat).
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-slate-700 dark:text-slate-300">
                                            Terakhir dikerjakan: <span class="font-semibold"><?php echo e(\App\Models\Ticket::formatDuration($rec['last_seconds'])); ?></span>
                                            <?php if($diffLast > 0): ?>
                                                <span class="text-xs text-slate-500 dark:text-slate-400">(+<?php echo e(\App\Models\Ticket::formatDuration($diffLast)); ?> dari rekornya)</span>
                                            <?php else: ?>
                                                <span class="text-xs text-green-600">(sama dengan rekornya)</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-slate-700 dark:text-slate-300">
                                            <?php if($rec['is_holder']): ?>
                                                <?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'star','color' => '#d4a017']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'star','color' => '#d4a017']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?> Pemegang rekor tercepat semua staf untuk tahap ini.
                                            <?php elseif($rec['holder']): ?>
                                                Rekor tercepat: <span class="font-semibold"><?php echo e($rec['holder']['name']); ?></span> <?php echo e(\App\Models\Ticket::formatDuration($rec['holder']['seconds'])); ?>

                                                <?php if($rec['gap_seconds'] !== null): ?>
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">(selisih +<?php echo e(\App\Models\Ticket::formatDuration($rec['gap_seconds'])); ?>)</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <div class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase mb-2">Papan rekor <?php echo e($rec['label']); ?></p>
                                        <div class="space-y-1">
                                            <?php $__currentLoopData = $shown; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $isMe = $b['user_id'] === $staff->id; ?>
                                                <div class="flex items-center justify-between gap-2 text-sm px-2 py-1 rounded-lg <?php echo e($isMe ? 'bg-purple-50 dark:bg-slate-700' : ''); ?>">
                                                    <span class="flex items-center gap-2 min-w-0">
                                                        <span class="w-6 text-xs text-slate-500 dark:text-slate-400">#<?php echo e($b['rank']); ?></span>
                                                        <?php if($isMe): ?>
                                                            <span class="truncate font-semibold text-slate-900 dark:text-white"><?php echo e($b['name']); ?> (ini)</span>
                                                        <?php else: ?>
                                                            <a href="<?php echo e(route('admin.reports.staff', ['user' => $b['user_id']] + $staffQuery)); ?>" class="truncate text-slate-700 dark:text-slate-300 hover:underline"><?php echo e($b['name']); ?></a>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="font-semibold text-slate-900 dark:text-white"><?php echo e(\App\Models\Ticket::formatDuration($b['seconds'])); ?></span>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Per tahap -->
                <?php if($stageSummary->count() > 0): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Per Tahap</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Rata-rata waktu cuma dihitung dari tiket yang sudah &quot;Selesai&quot;, sama seperti di halaman Laporan.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <?php $__currentLoopData = $stageSummary; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sum): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="p-3 bg-slate-50 dark:bg-slate-700 rounded-xl">
                                    <p class="font-medium text-slate-900 dark:text-white text-sm"><?php echo e($sum['label']); ?></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo e($sum['handled']); ?> tiket</p>
                                    <p class="font-bold text-slate-900 dark:text-white text-sm mt-2"><?php echo e(\App\Models\Ticket::formatDuration($sum['avg_seconds'])); ?></p>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Pekerjaan per hari / bulan -->
                <?php if(count($chart['bars']) > 1): ?>
                    <?php $maxBar = max(array_column($chart['bars'], 'count')) ?: 1; ?>
                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700 mb-8">
                        <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Pekerjaan per <?php echo e($chart['unit'] === 'month' ? 'Bulan' : 'Hari'); ?></h2>
                        <div class="overflow-x-auto">
                            <div class="flex items-end gap-1" style="height: 120px; min-width: max-content;">
                                <?php $__currentLoopData = $chart['bars']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bar): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="flex flex-col items-center justify-end <?php echo e($chart['unit'] === 'month' ? 'w-12' : 'w-8'); ?>" title="<?php echo e($bar['title']); ?> - <?php echo e($bar['count']); ?> pekerjaan">
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mb-1"><?php echo e($bar['count'] ?: ''); ?></span>
                                        <div class="w-full bg-blue-500 rounded-t-sm" style="height: <?php echo e($bar['count'] > 0 ? max(4, (int) round($bar['count'] / $maxBar * 80)) : 0); ?>px;"></div>
                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 mt-1"><?php echo e($bar['label']); ?></span>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Riwayat -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Riwayat Pengerjaan</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Satu baris = satu tahap yang dikerjakan <?php echo e($staff->name); ?> di satu tiket, terbaru di atas (<?php echo e($history->total()); ?> baris<?php echo e($search !== '' ? ', hasil pencarian' : ''); ?>). Kalau satu tiket dipegang di dua tahap (mis. Registrasi dan Proses Bayar), tampil dua baris. Klik nama pelanggan untuk lihat seluruh riwayat pelanggan itu.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-slate-500 dark:text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                                    <th class="pb-2 pr-4 whitespace-nowrap">Tanggal</th>
                                    <th class="pb-2 pr-4 whitespace-nowrap">Jam (Mulai - Selesai)</th>
                                    <th class="pb-2 pr-4">Tiket</th>
                                    <th class="pb-2 pr-4">Pelanggan</th>
                                    <th class="pb-2 pr-4">Tahap</th>
                                    <th class="pb-2 pr-4 whitespace-nowrap">Durasi Tahap</th>
                                    <th class="pb-2 pr-4 whitespace-nowrap">Total Tiket</th>
                                    <th class="pb-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <?php $__empty_1 = true; $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td class="py-2 pr-4 text-slate-600 dark:text-slate-300 whitespace-nowrap"><?php echo e($row['started_at']->format('d/m/Y')); ?></td>
                                        <td class="py-2 pr-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                            <?php echo e($row['started_at']->format('H:i:s')); ?> - <?php echo e($row['ended_at'] ? $row['ended_at']->format('H:i:s') : 'berjalan'); ?>

                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="font-medium text-slate-900 dark:text-white"><?php echo e($row['ticket_number']); ?></span>
                                            <?php if($row['location']): ?>
                                                <span class="block text-xs text-slate-500 dark:text-slate-400"><?php echo e($row['location']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 pr-4">
                                            <?php if($row['customer_phone']): ?>
                                                <a href="<?php echo e(route('admin.customers.show', ['phone' => $row['customer_phone']])); ?>" class="text-slate-700 dark:text-slate-300 hover:underline"><?php echo e($row['customer_name'] ?: '-'); ?></a>
                                                <span class="block text-xs text-slate-500 dark:text-slate-400"><?php echo e($row['customer_phone']); ?></span>
                                            <?php else: ?>
                                                <span class="text-slate-600 dark:text-slate-300"><?php echo e($row['customer_name'] ?: '-'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="font-medium text-slate-900 dark:text-white"><?php echo e($row['stage_label']); ?></span>
                                            <span class="block text-xs text-slate-500 dark:text-slate-400"><?php echo e($row['role']); ?></span>
                                        </td>
                                        <td class="py-2 pr-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                            <?php echo e(\App\Models\Ticket::formatDuration($row['seconds'])); ?>

                                            <?php if($row['ongoing']): ?>
                                                <span class="ml-1 text-xs font-normal text-amber-600">berjalan</span>
                                            <?php endif; ?>
                                            <?php if(!empty($row['record_break'])): ?>
                                                <span class="block text-xs font-normal text-green-600" title="Sebelumnya rekor <?php echo e(\App\Models\Ticket::formatDuration($row['record_break']['previous'])); ?>"><?php if (isset($component)) { $__componentOriginal776f3555c6e7e616238dd2f3602fe364 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal776f3555c6e7e616238dd2f3602fe364 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.papyrus-icon','data' => ['name' => 'arrow-trending-up','size' => '1em']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('papyrus-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-trending-up','size' => '1em']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $attributes = $__attributesOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__attributesOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal776f3555c6e7e616238dd2f3602fe364)): ?>
<?php $component = $__componentOriginal776f3555c6e7e616238dd2f3602fe364; ?>
<?php unset($__componentOriginal776f3555c6e7e616238dd2f3602fe364); ?>
<?php endif; ?> Pecah rekor (sebelumnya <?php echo e(\App\Models\Ticket::formatDuration($row['record_break']['previous'])); ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 pr-4 text-slate-500 dark:text-slate-400 whitespace-nowrap"><?php echo e(\App\Models\Ticket::formatDuration($row['total_seconds'])); ?></td>
                                        <td class="py-2">
                                            <?php if($row['status'] === 'completed'): ?>
                                                <span class="inline-block px-2 py-0.5 rounded-md text-xs bg-green-50 text-green-700 border border-green-200">Selesai</span>
                                            <?php elseif($row['status'] === 'cancelled'): ?>
                                                <span class="inline-block px-2 py-0.5 rounded-md text-xs bg-red-50 text-red-700 border border-red-200">Dibatalkan</span>
                                            <?php else: ?>
                                                <span class="inline-block px-2 py-0.5 rounded-md text-xs bg-amber-50 text-amber-700 border border-amber-200">Berjalan</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="8" class="py-6 text-center text-slate-500 dark:text-slate-400">Belum ada pekerjaan tercatat<?php echo e($search !== '' ? ' yang cocok dengan pencarian' : ' di periode ini'); ?>.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if($history->hasPages()): ?>
                        <div class="mt-4"><?php echo e($history->links()); ?></div>
                    <?php endif; ?>
                </div>

            </div>
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
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/admin/reports/staff.blade.php ENDPATH**/ ?>