<?php if (isset($component)) { $__componentOriginal23a33f287873b564aaf305a1526eada4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal23a33f287873b564aaf305a1526eada4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout','data' => ['title' => 'QR Order - ' . $location->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('QR Order - ' . $location->name)]); ?>
    <?php $__env->startPush('styles'); ?>
    <style>
        /* Cetak/PDF cuma kartu QR-nya saja (#print-area) -- apa pun elemen
           lain di halaman (navbar, footer, dekorasi "washi tape", dst)
           disembunyikan total pas print, bukan cuma diberi print:hidden di
           elemen itu sendiri. Ini lapis kedua yang lebih tegas: begitu ada
           #print-area di halaman, SEMUA elemen lain otomatis disembunyikan,
           jadi hasil cetak/PDF-nya gak pernah kebawa header/footer/nav
           halaman web.
        */
        @media print {
            body * {
                visibility: hidden;
            }
            #print-area, #print-area * {
                visibility: visible;
            }
            #print-area {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 380px;
                max-width: 90vw;
                box-shadow: none !important;
                border: 0 !important;
            }
            @page {
                margin: 0.5cm;
            }
        }
    </style>
    <?php $__env->stopPush(); ?>

    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 py-10 print:bg-white print:py-0">
        <div class="max-w-md mx-auto px-4">
            <div id="print-area" class="bg-white rounded-3xl shadow-xl border border-slate-200 p-10 text-center print:shadow-none print:border-0">
                <p class="text-sm font-semibold tracking-widest text-slate-400 uppercase mb-1"><?php echo e($location->name); ?></p>
                <h1 class="text-2xl font-black text-slate-900 mb-6">Scan untuk Ambil Antrian</h1>

                <div id="qrcode-large" class="inline-block p-4 bg-white border-4 border-slate-900 rounded-2xl"></div>

                <p class="text-slate-500 mt-6">
                    Arahkan kamera HP Anda ke QR code ini, isi data singkat, lalu Anda akan mendapatkan
                    nomor tiket dan link untuk memantau status pesanan.
                </p>

                <?php
                    // Font digedein & digelapin dari sebelumnya (text-xs
                    // text-slate-400) -- terlalu kecil/samar buat dibaca,
                    // apalagi buat pelanggan yang lebih tua. Titik potong
                    // baris juga dipindah: sebelumnya pakai break-all yang
                    // motong di SEMBARANG karakter (URL-nya kelihatan
                    // "kepotong" aneh kayak "...tickets.create/t/icket"),
                    // sekarang kasih <wbr> tepat setelah tiap "/" biar
                    // browser cuma pindah baris di batas segmen URL yang
                    // wajar, gak di tengah kata.
                    $ticketUrl = route('tickets.create', $location);
                    $ticketUrlWithBreaks = str_replace('/', '/<wbr>', e($ticketUrl));
                ?>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300 mt-6 leading-relaxed break-words"><?php echo $ticketUrlWithBreaks; ?></p>
            </div>

            <div class="mt-6 text-center print:hidden">
                <button onclick="window.print()" class="inline-flex items-center bg-gradient-to-r from-lavender to-dustyrose hover:opacity-90 text-white px-6 py-3 rounded-xl font-semibold">
                    Cetak QR
                </button>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
    <script type="module">
        if (document.getElementById('qrcode-large') && typeof QRCode !== 'undefined') {
            new QRCode(document.getElementById('qrcode-large'), {
                text: '<?php echo e(route('tickets.create', $location)); ?>',
                width: 260,
                height: 260,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
            });
        }
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
<?php /**PATH /var/www/html/resources/views/locations/scan.blade.php ENDPATH**/ ?>