<x-layout title="Hak Akses">
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900">
        <x-admin-header title="Hak Akses" subtitle="Kelompokkan hak akses supaya kelola pengguna lebih mudah" />

        <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 py-8 flex flex-col lg:flex-row gap-6 items-start">
            <x-admin-sidebar />
            <div class="flex-1 min-w-0 w-full space-y-6">

                @if(session('success'))
                    <div class="p-3 rounded-lg bg-green-50 text-green-800 text-sm border border-green-200">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="p-3 rounded-lg bg-red-50 text-red-800 text-sm border border-red-200">{{ $errors->first() }}</div>
                @endif

                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 text-sm text-slate-600 dark:text-slate-300">
                    Hak akses efektif pengguna = <b>bawaan role</b> + <b>grup akses</b> + <b>centang tambahan per-pengguna</b>.
                    Pasang grup ke pengguna lewat menu <a href="{{ route('admin.users') }}" class="text-blue-600 underline">Pengguna</a>.
                </div>

                {{-- Grup baru --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="p-4 border-b border-slate-100 dark:border-slate-700"><h2 class="font-semibold text-slate-900 dark:text-white">Buat Grup Baru</h2></div>
                    <form method="POST" action="{{ route('admin.access-groups.store') }}" class="p-4 space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input type="text" name="name" required maxlength="100" placeholder="Nama grup, mis. CS Reservasi" class="text-sm border border-slate-300 rounded px-2 py-1.5">
                            <input type="text" name="description" maxlength="255" placeholder="Keterangan (opsional)" class="text-sm border border-slate-300 rounded px-2 py-1.5">
                        </div>
                        @include('admin.access-groups._perms', ['selected' => [], 'catalog' => $catalog])
                        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded">Simpan Grup</button>
                    </form>
                </div>

                @forelse($groups as $g)
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                        <form method="POST" action="{{ route('admin.access-groups.update', $g) }}" class="p-4 space-y-3">
                            @csrf @method('PUT')
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $g->name }}
                                    <span class="ml-2 text-xs font-normal text-slate-500">{{ $g->users_count }} pengguna</span></h3>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <input type="text" name="name" required maxlength="100" value="{{ $g->name }}" class="text-sm border border-slate-300 rounded px-2 py-1.5">
                                <input type="text" name="description" maxlength="255" value="{{ $g->description }}" placeholder="Keterangan" class="text-sm border border-slate-300 rounded px-2 py-1.5">
                            </div>
                            @include('admin.access-groups._perms', ['selected' => $g->permissions ?? [], 'catalog' => $catalog])
                            <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded">Simpan</button>
                        </form>
                        <form method="POST" action="{{ route('admin.access-groups.destroy', $g) }}" class="px-4 pb-4" onsubmit="return confirm('Hapus grup {{ $g->name }}? Pengguna di grup ini jadi tanpa grup.')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-600 hover:underline">Hapus grup</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada grup.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layout>
