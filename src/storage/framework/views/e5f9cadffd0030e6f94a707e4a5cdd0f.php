<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'Kelola Pengguna']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kelola Pengguna']); ?>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <?php if (isset($component)) { $__componentOriginal489711a049975b0fbcd3875ea3652a04 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal489711a049975b0fbcd3875ea3652a04 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-header','data' => ['title' => 'Kelola Pengguna','subtitle' => 'Atur role dan loket tetap tiap pengguna']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Kelola Pengguna','subtitle' => 'Atur role dan loket tetap tiap pengguna']); ?>
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

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8 flex flex-col lg:flex-row gap-6 items-start">
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
            <div class="flex-1 min-w-0 w-full">
            <div class="mb-8">
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white text-sm inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>

        <div class="bg-white rounded-xl border border-slate-200 mb-6">
            <div class="p-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900">Tambah Pengguna Baru</h2>
                <p class="text-sm text-slate-500 mt-0.5">Buat akun untuk fotografer, CS/kasir, atau admin baru.</p>
            </div>
            <form method="POST" action="<?php echo e(route('admin.users.store')); ?>" class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                <?php echo csrf_field(); ?>
                <div class="lg:col-span-1">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Nama</label>
                    <input type="text" name="name" required value="<?php echo e(old('name')); ?>" class="w-full text-sm border border-slate-300 rounded px-2 py-1.5" placeholder="Contoh: Ivy">
                </div>
                <div class="lg:col-span-1">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Email</label>
                    <input type="email" name="email" required value="<?php echo e(old('email')); ?>" class="w-full text-sm border border-slate-300 rounded px-2 py-1.5" placeholder="ivy@papyrus.photo">
                </div>
                <div class="lg:col-span-1">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Password</label>
                    <input type="text" name="password" required minlength="8" class="w-full text-sm border border-slate-300 rounded px-2 py-1.5" placeholder="Minimal 8 karakter">
                </div>
                <div class="lg:col-span-1">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Role</label>
                    <select name="role" id="new-user-role" required class="w-full text-sm border border-slate-300 rounded px-2 py-1.5" onchange="filterCounterOptionsByRole(document.getElementById('new-user-counter'), this.value)">
                        <option value="fotografer">Fotografer</option>
                        <option value="operator">CS & Kasir</option>
                        <option value="admin">Admin</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Loket Tetap <span class="font-normal text-slate-400">(opsional)</span></label>
                    <select name="preferred_counter_id" id="new-user-counter" class="w-full text-sm border border-slate-300 rounded px-2 py-1.5">
                        <option value="">-- Pilihan (tanya lokasi & loket tiap login) --</option>
                        <?php $__currentLoopData = $counters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($c->id); ?>" data-stages="<?php echo e(implode(',', $c->stages ?? [])); ?>"><?php echo e($c->location->name); ?> — <?php echo e($c->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="lg:col-span-1">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-1.5 rounded">Tambah Pengguna</button>
                </div>
            </form>
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="text-xs text-red-600 px-4 pb-3"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="bg-white rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Nama</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Email</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Role</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Loket Tetap</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body" class="divide-y divide-slate-200">
                        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <?php if(in_array($user->role, ['operator', 'fotografer'], true)): ?>
                                        <a href="<?php echo e(route('admin.reports.staff', ['user' => $user->id])); ?>" class="font-medium text-slate-900 hover:underline" title="Lihat detail kinerja &amp; rekor <?php echo e($user->name); ?>"><?php echo e($user->name); ?></a>
                                    <?php else: ?>
                                        <span class="font-medium text-slate-900"><?php echo e($user->name); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600"><?php echo e($user->email); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        <?php echo e($user->role === 'admin' ? 'bg-purple-100 text-purple-800' : ''); ?>

                                        <?php echo e($user->role === 'operator' ? 'bg-blue-100 text-blue-800' : ''); ?>

                                        <?php echo e($user->role === 'fotografer' ? 'bg-amber-100 text-amber-800' : ''); ?>

                                        <?php echo e($user->role === 'viewer' ? 'bg-slate-100 text-slate-800' : ''); ?>

                                    ">
                                        <?php echo e($user->role === 'operator' ? 'CS & Kasir' : ucfirst($user->role)); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <?php echo e($user->preferredCounter?->name ?? '-'); ?>

                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3" x-data="{ editOpen: false }">
                                        <?php if(in_array($user->role, ['operator', 'fotografer'], true)): ?>
                                            <a href="<?php echo e(route('admin.reports.staff', ['user' => $user->id])); ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Kinerja</a>
                                        <?php endif; ?>
                                        <button type="button" @click="editOpen = true; $nextTick(() => $refs.editNameInput.focus())" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                            Ubah
                                        </button>

                                        <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>" onsubmit="return confirmFormSubmit(this, { message: 'Hapus pengguna &quot;<?php echo e($user->name); ?>&quot;? Tindakan ini tidak bisa dibatalkan.', variant: 'danger', confirmText: 'Ya, Hapus' })">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="text-red-600 hover:text-red-700 text-sm font-medium">Hapus</button>
                                        </form>

                                        <!-- Modal Ubah Pengguna -->
                                        <div x-show="editOpen" x-cloak
                                             class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                             style="background: rgba(15, 23, 42, 0.5);"
                                             @click.self="editOpen = false"
                                             @keydown.escape.window="editOpen = false">
                                            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                                                <form method="POST" action="<?php echo e(route('admin.users.update', $user)); ?>" class="p-5 space-y-4 text-left">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PUT'); ?>

                                                    <div class="flex items-center justify-between">
                                                        <h3 class="font-semibold text-slate-900 dark:text-white">Ubah Pengguna</h3>
                                                        <button type="button" @click="editOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl leading-none">&times;</button>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Nama</label>
                                                        <input type="text" name="name" required value="<?php echo e($user->name); ?>" x-ref="editNameInput" class="w-full text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded px-2 py-1.5">
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Email</label>
                                                        <input type="email" name="email" required value="<?php echo e($user->email); ?>" class="w-full text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded px-2 py-1.5">
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                                                            Password Baru <span class="font-normal text-slate-400">(kosongkan kalau tidak diubah)</span>
                                                        </label>
                                                        <input type="text" name="password" minlength="8" placeholder="Minimal 8 karakter" class="w-full text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded px-2 py-1.5">
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Role</label>
                                                            <select name="role" class="edit-role-select w-full text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded px-2 py-1.5" onchange="filterCounterOptionsByRole(this.closest('form').querySelector('.edit-counter-select'), this.value)">
                                                                <option value="viewer" <?php echo e($user->role === 'viewer' ? 'selected' : ''); ?>>Viewer</option>
                                                                <option value="operator" <?php echo e($user->role === 'operator' ? 'selected' : ''); ?>>CS & Kasir</option>
                                                                <option value="fotografer" <?php echo e($user->role === 'fotografer' ? 'selected' : ''); ?>>Fotografer</option>
                                                                <option value="admin" <?php echo e($user->role === 'admin' ? 'selected' : ''); ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1" title="Loket/studio tetap — biar konsisten tiap login, gak gonta-ganti">Loket Tetap</label>
                                                            <select name="preferred_counter_id" class="edit-counter-select w-full text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded px-2 py-1.5">
                                                                <option value="">-- Pilihan (tanya tiap login) --</option>
                                                                <?php $__currentLoopData = $counters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <option value="<?php echo e($c->id); ?>" data-stages="<?php echo e(implode(',', $c->stages ?? [])); ?>" <?php echo e($user->preferred_counter_id === $c->id ? 'selected' : ''); ?>>
                                                                        <?php echo e($c->location->name); ?> — <?php echo e($c->name); ?>

                                                                    </option>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <?php $perms = $user->permissions ?? []; ?>
                                                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-2">Hak akses</label>
                                                        <div class="flex flex-wrap gap-3">
                                                            <label class="inline-flex items-center space-x-1 text-xs text-slate-700 dark:text-slate-300">
                                                                <input type="checkbox" name="permissions[walkin]" value="1" class="rounded" <?php echo e(in_array('walkin', $perms) ? 'checked' : ''); ?>>
                                                                <span>Walk-in</span>
                                                            </label>
                                                            <label class="inline-flex items-center space-x-1 text-xs text-slate-700 dark:text-slate-300">
                                                                <input type="checkbox" name="permissions[booking]" value="1" class="rounded" <?php echo e(in_array('booking', $perms) ? 'checked' : ''); ?>>
                                                                <span>Reservasi</span>
                                                            </label>
                                                            <label class="inline-flex items-center space-x-1 text-xs text-slate-700 dark:text-slate-300">
                                                                <input type="checkbox" name="permissions[photo]" value="1" class="rounded" <?php echo e(in_array('photo', $perms) ? 'checked' : ''); ?>>
                                                                <span>Panggil Foto</span>
                                                            </label>
                                                            <label class="inline-flex items-center space-x-1 text-xs text-slate-700 dark:text-slate-300">
                                                                <input type="checkbox" name="permissions[payment]" value="1" class="rounded" <?php echo e(in_array('payment', $perms) ? 'checked' : ''); ?>>
                                                                <span>Pembayaran</span>
                                                            </label>
                                                            <label class="inline-flex items-center space-x-1 text-xs text-slate-700 dark:text-slate-300">
                                                                <input type="checkbox" name="permissions[customers]" value="1" class="rounded" <?php echo e(in_array('customers', $perms) ? 'checked' : ''); ?>>
                                                                <span>Data Pelanggan</span>
                                                            </label>
                                                            <label class="inline-flex items-center space-x-1 text-xs text-slate-700 dark:text-slate-300">
                                                                <input type="checkbox" name="permissions[all_locations]" value="1" class="rounded" <?php echo e(in_array('all_locations', $perms) ? 'checked' : ''); ?>>
                                                                <span>Semua Lokasi</span>
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                                                        <button type="button" @click="editOpen = false" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white">Batal</button>
                                                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded">Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script>
        // Aman: helper ini otomatis skip refresh selama admin sedang fokus
        // (klik/pilih dropdown) di dalam tabel ini, supaya perubahan yang
        // belum di-"Simpan" tidak ke-reset tiba-tiba.
        window.autoRefreshIds(['users-table-body'], 6000);

        // Loket Tetap cuma ditampilkan kalau beneran cocok buat tahap kerja
        // role itu -- Fotografer gak akan lihat loket CS & Kasir sebagai
        // pilihan, dan sebaliknya. Sama logikanya kayak halaman "pilih
        // lokasi" waktu login (lihat OperatorController::selectableLocationsQuery()).
        // Admin & Viewer gak difilter (gak terikat ke loket tertentu).
        const ROLE_STAGES = {
            fotografer: ['photo_session'],
            operator: ['registration', 'payment'],
        };

        function filterCounterOptionsByRole(selectEl, role) {
            if (!selectEl) return;
            const requiredStages = ROLE_STAGES[role];
            Array.from(selectEl.options).forEach(function (opt) {
                if (!opt.value) return; // placeholder selalu kelihatan
                if (!requiredStages) {
                    opt.hidden = false;
                    return;
                }
                const stages = (opt.dataset.stages || '').split(',').filter(Boolean);
                const relevant = stages.some(function (s) { return requiredStages.includes(s); });
                opt.hidden = !relevant;
                if (!relevant && opt.selected) selectEl.value = '';
            });
        }

        filterCounterOptionsByRole(document.getElementById('new-user-counter'), document.getElementById('new-user-role').value);

        document.querySelectorAll('.edit-counter-select').forEach(function (sel) {
            const roleSelect = sel.closest('form').querySelector('.edit-role-select');
            filterCounterOptionsByRole(sel, roleSelect.value);
        });
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
<?php /**PATH /var/www/html/resources/views/admin/users/index.blade.php ENDPATH**/ ?>