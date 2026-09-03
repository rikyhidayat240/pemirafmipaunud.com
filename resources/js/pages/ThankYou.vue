<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
</script>

<template>
    <Head title="Terima Kasih" />

    <AppLayout>
        <div class="flex min-h-[85vh] flex-col items-center justify-center relative overflow-hidden px-4">


            <!-- Main content -->
            <div class="relative z-10 flex flex-col items-center text-center max-w-lg gap-6">

                <!-- Glowing Logo -->
                <div class="relative">
                    <div class="absolute inset-0 rounded-full"
                        style="background: radial-gradient(circle, rgba(201,162,39,0.4) 0%, transparent 70%); transform: scale(3); animation: shimmer-gold 3s ease-in-out infinite;"></div>
                    <img src="/Logo pemira.png" alt="Logo PEMIRA"
                        class="relative size-20 sm:size-24 drop-shadow-2xl object-contain"
                        onerror="this.style.display='none'" />
                </div>

                <!-- Text -->
                <div class="space-y-2">
                    <p class="text-xs text-yellow-400/60 uppercase tracking-[0.3em] font-semibold">
                        Suara Anda Telah Direkam
                    </p>
                    <h1 class="text-3xl sm:text-4xl font-black text-white poppins-font leading-tight">
                        Terima Kasih Telah
                    </h1>
                    <h2 class="text-3xl sm:text-4xl font-black poppins-font" style="color: #f0c040;">
                        Berpartisipasi!
                    </h2>
                </div>

                <!-- Description -->
                <p class="text-sm sm:text-base text-white/50 leading-relaxed max-w-sm">
                    Suara Anda telah berhasil direkam dalam sistem PEMIRA LM FMIPA {{ dayjs().year() }}.
                    Hasil pemilihan akan diumumkan setelah periode voting berakhir.
                    Suara Anda untuk <span class="text-yellow-400 font-semibold">BEM & HIMA Prodi</span> telah berhasil direkam. Kembali ke
                    dashboard untuk melanjutkan hak pilih Anda lain.
                </p>

                <!-- CTA -->
                <Link :href="route('dashboard')">
                    <button class="btn-gold px-8 py-3 rounded-full text-sm font-bold tracking-wide"
                        style="box-shadow: 0 0 30px rgba(201,162,39,0.4);">
                        ➜ Kembali ke Dashboard
                    </button>
                </Link>
            </div>

            <!-- Bottom text -->
            <p class="absolute bottom-8 text-xs text-white/20 italic">
                FMIPA LM FMIPA {{ dayjs().year() }} - Satu Suara untuk Masa Depan
            </p>
        </div>
    </AppLayout>
</template>
