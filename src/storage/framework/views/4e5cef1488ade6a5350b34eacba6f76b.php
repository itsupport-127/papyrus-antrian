<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Histori Pelanggan']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Histori Pelanggan']); ?>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="<?php echo e(route('admin.customers')); ?>" class="text-blue-600 hover:text-blue-700 text-sm mb-2 inline-flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali ke Data Pelanggan
            </a>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo e($summary['name'] ?? 'Pelanggan'); ?></h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1"><?php echo e($summary['phone']); ?></p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400">Total Order</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?php echo e($summary['total_orders']); ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400">Selesai</p>
                <p class="text-2xl font-bold text-green-600 mt-1"><?php echo e($summary['completed_orders']); ?></p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5">
                <p class="text-sm text-slate-500 dark:text-slate-400">Order Terakhir</p>
                <p class="text-lg font-bold text-slate-900 dark:text-white mt-1"><?php echo e(\Carbon\Carbon::parse($summary['last_order_at'])->diffForHumans()); ?></p>
            </div>
        </div>

        <!-- Edit Contact Info -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 mb-8">
            <h2 class="font-bold text-slate-900 dark:text-white mb-4">Edit Data Kontak</h2>
            <form method="POST" action="<?php echo e(route('admin.customers.update', $summary['phone'])); ?>" class="grid sm:grid-cols-2 gap-4">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">Nama</label>
                    <input type="text" name="customer_name" value="<?php echo e($summary['name']); ?>" required
                        class="w-full px-3 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">Email</label>
                    <input type="email" name="customer_email" value="<?php echo e($summary['email']); ?>"
                        class="w-full px-3 py-2.5 border-2 border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white rounded-xl font-semibold text-sm">
                        Simpan Perubahan
                    </button>
                    <span class="text-xs text-slate-400 ml-2">Perubahan berlaku untuk semua histori pesanan no. telepon ini.</span>
                </div>
            </form>
        </div>

        <!-- Order History -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-700">
                <h2 class="font-bold text-slate-900 dark:text-white">Histori Pesanan</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/40">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">No. Tiket</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tanggal</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Lokasi</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Status</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Order</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="px-6 py-4 font-semibold text-slate-900 dark:text-white"><?php echo e($ticket->ticket_number); ?></td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?php echo e($ticket->created_at->format('d M Y, H:i')); ?></td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-300"><?php echo e($ticket->location?->name ?? '-'); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?php echo e($ticket->status === 'completed' ? 'bg-green-100 text-green-700' : ''); ?>

                                        <?php echo e($ticket->status === 'cancelled' ? 'bg-red-100 text-red-700' : ''); ?>

                                        <?php echo e(in_array($ticket->status, ['waiting', 'calling', 'serving', 'editing']) ? 'bg-amber-100 text-amber-700' : ''); ?>">
                                        <?php echo e($ticket->status === 'completed' ? 'Selesai' : ($ticket->status === 'cancelled' ? 'Dibatalkan' : $ticket->stageLabel())); ?>

                                    </span>
                                    <?php if($ticket->skip_count > 0): ?>
                                        <span class="block text-xs text-amber-600 mt-1">Dilewati <?php echo e($ticket->skip_count); ?>x</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400 max-w-xs whitespace-pre-line break-words"><?php echo e($ticket->notes ?? '-'); ?></td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="<?php echo e(route('admin.customers.tickets.delete', [$phone, $ticket])); ?>"
                                          onsubmit="return confirm('Hapus riwayat tiket <?php echo e($ticket->ticket_number); ?>? Tindakan ini tidak bisa dibatalkan.');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
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
<?php /**PATH /var/www/html/resources/views/admin/customers/show.blade.php ENDPATH**/ ?>