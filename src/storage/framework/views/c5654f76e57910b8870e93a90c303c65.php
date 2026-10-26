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
        <?php if (isset($component)) { $__componentOriginal489711a049975b0fbcd3875ea3652a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal489711a049975b0fbcd3875ea3652a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-header','data' => ['title' => 'Data Pelanggan','subtitle' => 'Dikumpulkan otomatis dari histori tiket, dikelompokkan per no. telepon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Data Pelanggan','subtitle' => 'Dikumpulkan otomatis dari histori tiket, dikelompokkan per no. telepon']); ?>
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
        <?php
            $bulanId = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
            $isFiltered = $search !== '' || $filter['range'] !== 'all';
            $inputCls = 'px-4 py-2 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-white';
            $labelCls = 'block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1';
        ?>

        <style>
            /* Tab periode (Semua / Hari / Minggu / Bulan / Tahun / Custom). CSS biasa,
               bukan utility Tailwind, supaya gak butuh build ulang aset. */
            .cf-seg { display: inline-flex; flex-wrap: wrap; gap: 4px; padding: 4px; border-radius: 12px; background: #f1f5f9; border: 1px solid #e2e8f0; }
            .cf-seg label { position: relative; cursor: pointer; }
            .cf-seg input { position: absolute; opacity: 0; pointer-events: none; }
            .cf-seg span { display: block; padding: 6px 14px; border-radius: 9px; font-size: 13px; font-weight: 600; color: #64748b; }
            .cf-seg input:checked + span { background: #fff; color: #7c3aed; box-shadow: 0 1px 2px rgba(0, 0, 0, .08); }
            .cf-seg input:focus-visible + span { outline: 2px solid #c5a4e8; }
            html.dark .cf-seg { background: #0f172a; border-color: #334155; }
            html.dark .cf-seg span { color: #94a3b8; }
            html.dark .cf-seg input:checked + span { background: #334155; color: #e9d5ff; }
        </style>

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">Data Pelanggan</h1>

            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4"
                 x-data="{ range: '<?php echo e($filter['range']); ?>' }">
                <form method="GET" action="<?php echo e(route('admin.customers')); ?>" class="flex flex-wrap gap-4 items-end">

                    <div>
                        <label class="<?php echo e($labelCls); ?>">Cari</label>
                        <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Cari nama atau no. telepon..." class="w-64 <?php echo e($inputCls); ?>">
                    </div>

                    <div>
                        <span class="<?php echo e($labelCls); ?>">Periode order</span>
                        <div class="cf-seg" role="radiogroup" aria-label="Periode order">
                            <?php $__currentLoopData = ['all' => 'Semua', 'day' => 'Hari', 'week' => 'Minggu', 'month' => 'Bulan', 'year' => 'Tahun', 'custom' => 'Custom']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $text): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label><input type="radio" name="range" value="<?php echo e($val); ?>" x-model="range"><span><?php echo e($text); ?></span></label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    
                    <div x-show="range === 'day' || range === 'week'" x-cloak>
                        <label class="<?php echo e($labelCls); ?>" x-text="range === 'week' ? 'Tanggal dalam minggu' : 'Tanggal'"></label>
                        <input type="date" name="date" value="<?php echo e($filter['date']); ?>" class="<?php echo e($inputCls); ?>" :disabled="!(range === 'day' || range === 'week')">
                    </div>

                    
                    <div x-show="range === 'month'" x-cloak class="flex gap-3">
                        <div>
                            <label class="<?php echo e($labelCls); ?>">Bulan</label>
                            <select name="month" class="<?php echo e($inputCls); ?>" :disabled="range !== 'month'">
                                <?php $__currentLoopData = $bulanId; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $num => $nama): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($num); ?>" <?php echo e((int) $filter['month'] === $num ? 'selected' : ''); ?>><?php echo e($nama); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div>
                            <label class="<?php echo e($labelCls); ?>">Tahun</label>
                            <select name="year" class="<?php echo e($inputCls); ?>" :disabled="range !== 'month'">
                                <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($y); ?>" <?php echo e((int) $filter['year'] === $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </div>

                    
                    <div x-show="range === 'year'" x-cloak>
                        <label class="<?php echo e($labelCls); ?>">Tahun</label>
                        <select name="year" class="<?php echo e($inputCls); ?>" :disabled="range !== 'year'">
                            <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($y); ?>" <?php echo e((int) $filter['year'] === $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    
                    <div x-show="range === 'custom'" x-cloak class="flex gap-3">
                        <div>
                            <label class="<?php echo e($labelCls); ?>">Dari</label>
                            <input type="date" name="from" value="<?php echo e($filter['from']); ?>" class="<?php echo e($inputCls); ?>" :disabled="range !== 'custom'">
                        </div>
                        <div>
                            <label class="<?php echo e($labelCls); ?>">Sampai</label>
                            <input type="date" name="to" value="<?php echo e($filter['to']); ?>" class="<?php echo e($inputCls); ?>" :disabled="range !== 'custom'">
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-xl text-sm font-semibold">Terapkan</button>
                        <?php if($isFiltered): ?>
                            <a href="<?php echo e(route('admin.customers')); ?>" class="px-4 py-2 border-2 border-slate-200 dark:border-slate-600 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <p id="customers-summary" class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                <?php if($filter['label']): ?>
                    Order periode <span class="font-semibold text-slate-700 dark:text-slate-200"><?php echo e($filter['label']); ?></span>
                <?php else: ?>
                    Semua periode
                <?php endif; ?>
                &middot; <?php echo e(number_format($customers->total(), 0, ',', '.')); ?> pelanggan &middot; <?php echo e(number_format($totalOrders, 0, ',', '.')); ?> order
            </p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-900/40">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Nama</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">No. Telepon</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Email</th>
                            <th class="text-right px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Total Order</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Order Terakhir</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="customers-table-body" class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php $__empty_1 = true; $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white"><?php echo e($c->customer_name ?? '-'); ?></td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?php echo e($c->customer_phone); ?></td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?php echo e($c->customer_email ?? '-'); ?></td>
                                <td class="px-6 py-4 text-right text-slate-900 dark:text-white"><?php echo e($c->total_orders); ?></td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?php echo e(\Carbon\Carbon::parse($c->last_order_at)->diffForHumans()); ?></td>
                                <td class="px-6 py-4">
                                    <a href="<?php echo e(route('admin.customers.show', $c->customer_phone)); ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        Lihat Histori
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">
                                    <?php echo e($isFiltered ? 'Tidak ada pelanggan yang cocok dengan filter ini.' : 'Belum ada data pelanggan.'); ?>

                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <?php echo e($customers->links()); ?>

        </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        // Tabel ikut update otomatis; form pencarian di atas tidak disentuh
        // sama sekali jadi ketikan admin tetap aman.
        window.autoRefreshIds(['customers-summary', 'customers-table-body'], 8000);
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
<?php /**PATH /var/www/html/resources/views/admin/customers/index.blade.php ENDPATH**/ ?>