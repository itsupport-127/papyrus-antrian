<div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
    <div class="papyrus-tape"></div>
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10">
        <div class="flex flex-wrap items-center justify-between gap-3 py-3 sm:py-0 sm:h-16">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 shrink-0 bg-gradient-to-br from-lavender via-skyblue to-dustyrose rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h1 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-white truncate">{{ $title }}</h1>
                    @if($subtitle ?? null)
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 truncate">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-1 sm:gap-3 shrink-0">
                <a href="{{ route('home') }}" target="_blank" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white px-2 sm:px-3 py-2 text-sm font-medium rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/50" title="Lihat Situs">
                    <svg class="w-5 h-5 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span class="hidden sm:inline">Lihat Situs</span>
                </a>
            </div>
        </div>
        {{-- Baris tab horizontal yang dulu ada di sini sudah dipindah jadi
             <x-admin-sidebar /> (sidebar kiri di layar lebar, baris scroll
             horizontal di HP) -- dipasang di tiap halaman admin langsung,
             bukan di sini, karena sidebar perlu sejajar DI SAMPING konten,
             bukan di ATAS konten seperti baris tab yang lama. --}}
    </div>
</div>
