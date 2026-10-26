<x-layout title="Kelola Pengguna">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Kelola Pengguna" subtitle="Atur role pengguna dan assign operator ke lokasi" />

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8">
            <div class="mb-8">
                <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white text-sm inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>

        <div class="bg-white rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Nama</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Email</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Role</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Lokasi</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="users-table-body" class="divide-y divide-slate-200">
                        @foreach($users as $user)
                            <tr>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-900">{{ $user->name }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $user->email }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-medium
                                        {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $user->role === 'operator' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $user->role === 'fotografer' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $user->role === 'viewer' ? 'bg-slate-100 text-slate-800' : '' }}
                                    ">
                                        {{ $user->role === 'operator' ? 'CS & Kasir' : ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $user->location?->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <div class="flex flex-wrap gap-2">
                                    <select name="role" class="text-sm border border-slate-300 rounded px-2 py-1">
                                        <option value="viewer" {{ $user->role === 'viewer' ? 'selected' : '' }}>Viewer</option>
                                        <option value="operator" {{ $user->role === 'operator' ? 'selected' : '' }}>CS & Kasir</option>
                                        <option value="fotografer" {{ $user->role === 'fotografer' ? 'selected' : '' }}>Fotografer</option>
                                        <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <select name="location_id" class="text-sm border border-slate-300 rounded px-2 py-1">
                                        <option value="">-- Tanpa Lokasi --</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ $user->location_id === $loc->id ? 'selected' : '' }}>
                                                {{ $loc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                        Simpan
                                    </button>
                                </div>
                                <div x-data="{ open: false }" class="mt-2">
                                    <button type="button" @click="open = !open" class="text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400">
                                        <span x-text="open ? 'Tutup' : 'Hak akses'"></span>
                                    </button>
                                    <div x-show="open" x-cloak class="mt-2 flex flex-wrap gap-2">
                                        @php
                                            $perms = $user->permissions ?? [];
                                        @endphp
                                        <label class="inline-flex items-center space-x-1 text-xs">
                                            <input type="checkbox" name="permissions[walkin]" value="1" class="rounded" {{ in_array('walkin', $perms) ? 'checked' : '' }}>
                                            <span>Walk-in</span>
                                        </label>
                                        <label class="inline-flex items-center space-x-1 text-xs">
                                            <input type="checkbox" name="permissions[booking]" value="1" class="rounded" {{ in_array('booking', $perms) ? 'checked' : '' }}>
                                            <span>Reservasi</span>
                                        </label>
                                        <label class="inline-flex items-center space-x-1 text-xs">
                                            <input type="checkbox" name="permissions[photo]" value="1" class="rounded" {{ in_array('photo', $perms) ? 'checked' : '' }}>
                                            <span>Panggil Foto</span>
                                        </label>
                                        <label class="inline-flex items-center space-x-1 text-xs">
                                            <input type="checkbox" name="permissions[payment]" value="1" class="rounded" {{ in_array('payment', $perms) ? 'checked' : '' }}>
                                            <span>Pembayaran</span>
                                        </label>
                                        <label class="inline-flex items-center space-x-1 text-xs">
                                            <input type="checkbox" name="permissions[customers]" value="1" class="rounded" {{ in_array('customers', $perms) ? 'checked' : '' }}>
                                            <span>Data Pelanggan</span>
                                        </label>
                                        <label class="inline-flex items-center space-x-1 text-xs">
                                            <input type="checkbox" name="permissions[all_locations]" value="1" class="rounded" {{ in_array('all_locations', $perms) ? 'checked' : '' }}>
                                            <span>Semua Lokasi</span>
                                        </label>
                                    </div>
                                </div>
                            </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Aman: helper ini otomatis skip refresh selama admin sedang fokus
        // (klik/pilih dropdown) di dalam tabel ini, supaya perubahan yang
        // belum di-"Simpan" tidak ke-reset tiba-tiba.
        window.autoRefreshIds(['users-table-body'], 6000);
    </script>
    @endpush
</x-layout>
