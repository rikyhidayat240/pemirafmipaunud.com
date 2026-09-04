<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <!-- Full-page dark space background -->
    <div class="relative min-h-svh flex flex-col items-center justify-center overflow-hidden"
        style="background-image: url('/StarfieldCanvas.png'); background-size: cover; background-position: center; background-attachment: fixed;">

        <!-- Image decorations from Figma (scaled with viewport to fix zoom) -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
            <!-- Large Constellation (Group 186) -->
            <img src="/group-186.png" class="absolute top-[5%] left-[2%] w-[30vw] max-w-[350px] min-w-[150px] opacity-80 animate-pulse" style="animation-duration: 6s;" alt="">
            
            <!-- Kite constellation (Group 220) -->
            <img src="/group-220.png" class="absolute top-[2%] right-[10%] w-[20vw] max-w-[200px] min-w-[80px] opacity-80 animate-pulse" style="animation-duration: 8s;" alt="">
            <!-- Huge 4-pointed Star (Group 227) -->
            <img src="/group-227.png" class="absolute bottom-[5%] right-[2%] w-[20vw] max-w-[250px] min-w-[120px] opacity-70 animate-pulse" style="animation-duration: 6s;" alt="">
            
            <!-- Static decorative stars -->
            <div class="absolute top-[15%] left-[25%] size-0.5 rounded-full bg-white/30" style="animation: twinkle 4s ease-in-out infinite 0.5s;"></div>
            <div class="absolute top-[30%] right-[15%] size-0.5 rounded-full bg-white/25" style="animation: twinkle 4.5s ease-in-out infinite 1.2s;"></div>
            <div class="absolute top-[55%] right-[8%] size-1 rounded-full bg-white/15" style="animation: twinkle 5.5s ease-in-out infinite 0.6s;"></div>
            <div class="absolute top-[75%] left-[20%] size-0.5 rounded-full bg-yellow-400/25" style="animation: twinkle 4.2s ease-in-out infinite 2s;"></div>
            <div class="absolute top-[90%] left-[60%] size-0.5 rounded-full bg-yellow-400/15" style="animation: twinkle 5.8s ease-in-out infinite 1.8s;"></div>
        </div>

        <!-- Main Login Card -->
        <div class="relative z-10 w-full max-w-sm mx-4">
            <div class="pemira-card rounded-2xl shadow-2xl p-8 backdrop-blur-md relative overflow-hidden"
                style="box-shadow: 0 0 60px rgba(201,162,39,0.1), 0 20px 60px rgba(0,0,0,0.5);">

                <!-- Corner ornaments (Group 187) as border inside the card -->
                <img src="/Group 187.png" class="absolute top-0 left-0 w-16 opacity-70 mix-blend-screen pointer-events-none" alt="">
                <img src="/Group 187.png" class="absolute bottom-0 right-0 w-16 opacity-70 mix-blend-screen pointer-events-none rotate-180" alt="">

                <div class="relative z-10">
                    <!-- Logo + Title -->
                    <div class="flex flex-col items-center gap-3 mb-6 mt-2">
                        <Link :href="route('home')" class="flex flex-col items-center gap-2">
                            <!-- Gunakan logo baru -->
                            <img src="/Logo pemira.png" alt="Logo PEMIRA"
                                class="h-20 drop-shadow-lg object-contain"
                                onerror="this.style.display='none'" />
                        </Link>
                        <div class="text-center mt-2">
                            <h1 class="text-xl font-bold text-white">{{ title }}</h1>
                            <p class="text-xs text-yellow-400/70 font-medium uppercase tracking-widest mt-1">
                                PEMIRA LM FMIPA {{ dayjs().year() }}
                            </p>
                            <div class="h-px w-24 mx-auto mt-2"
                                style="background: linear-gradient(90deg, transparent, rgba(201,162,39,0.6), transparent);"></div>
                        </div>
                    </div>

                    <!-- Form Slot -->
                    <slot />
                </div>
            </div>
        </div>

        <!-- Footer bar -->
        <div class="relative z-10 mt-6 text-center">
            <p class="text-xs text-white/20">
                PEMIRA LM FMIPA {{ dayjs().year() }} · Satu Suara untuk Masa Depan
            </p>
        </div>
    </div>
</template>
