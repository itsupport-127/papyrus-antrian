<div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
    <div class="papyrus-tape"></div>
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10">
        <div class="flex flex-wrap items-center justify-between gap-3 py-3 sm:py-0 sm:h-16">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 shrink-0 bg-gradient-to-br from-lavender via-skyblue to-dustyrose rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
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

        {{-- Tab "Dashboard / Jadwal Reservasi / Master Data Pelanggan" dihapus (navigasi utama ada di bar atas). --}}
    </div>
</div>
