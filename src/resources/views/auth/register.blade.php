<x-layout title="Daftar">
    <div class="min-h-[calc(100vh-4rem)] bg-gradient-to-b from-periwinkle/60 via-slate-50 to-blush/30 dark:from-slate-900 dark:via-slate-900 dark:to-slate-900 py-12">
        <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl dark:shadow-slate-900/50 overflow-hidden">
                <!-- Header -->
                <div class="papyrus-swirl-bg bg-gradient-to-br from-lavender to-dustyrose text-white p-8 text-center">
                    <div class="w-16 h-16 bg-white/15 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <h1 class="font-display text-2xl font-bold">Daftar Akun Baru</h1>
                    <p class="text-white/80 mt-2 text-sm">Buat akun untuk mulai menggunakan {{ config('app.name') }}</p>
                </div>

                <div class="p-8">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-lavender focus:border-lavender bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-lavender focus:border-lavender bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Password</label>
                                <input type="password" id="password" name="password" required
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-lavender focus:border-lavender bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Konfirmasi Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" required
                                    class="w-full px-4 py-3 border border-slate-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-lavender focus:border-lavender bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-6 bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white py-3 rounded-lg font-medium shadow-lg shadow-lavender/30 transition-all hover:shadow-xl">
                            Daftar
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-slate-600 dark:text-slate-400 text-sm">
                            Sudah punya akun?
                            <a href="{{ route('login') }}" class="text-lavender hover:opacity-80 font-medium">Masuk</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>
