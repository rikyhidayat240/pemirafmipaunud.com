<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>

    <div class="relative min-h-svh flex flex-col items-center justify-center overflow-hidden"
        style="background: linear-gradient(160deg, #0d1033 0%, #111635 40%, #14193f 70%, #0a0d22 100%);">

        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
            <img src="/Group 186.png" class="absolute top-[10%] left-[5%] w-[30vw] max-w-[200px] min-w-[100px] opacity-80 animate-pulse" style="animation-duration: 4s;" alt="">


            <img src="/Group 220.png" class="absolute top-[20%] right-[15%] w-[15vw] max-w-[120px] min-w-[60px] opacity-70" style="animation: float-star 8s ease-in-out infinite;" alt="">
            <img src="/Group 220.png" class="absolute bottom-[20%] left-[10%] w-[10vw] max-w-[90px] min-w-[50px] opacity-60" style="animation: float-star 6s ease-in-out infinite 2s; transform: scaleX(-1);" alt="">
            
            <div class="absolute top-[15%] left-[25%] size-0.5 rounded-full bg-white/30" style="animation: twinkle 4s ease-in-out infinite 0.5s;"></div>
            <div class="absolute top-[30%] right-[15%] size-0.5 rounded-full bg-white/25" style="animation: twinkle 4.5s ease-in-out infinite 1.2s;"></div>
            <div class="absolute top-[55%] right-[8%] size-1 rounded-full bg-white/15" style="animation: twinkle 5.5s ease-in-out infinite 0.6s;"></div>
            <div class="absolute top-[75%] left-[20%] size-0.5 rounded-full bg-yellow-400/25" style="animation: twinkle 4.2s ease-in-out infinite 2s;"></div>
            <div class="absolute top-[90%] left-[60%] size-0.5 rounded-full bg-yellow-400/15" style="animation: twinkle 5.8s ease-in-out infinite 1.8s;"></div>
        </div>

        <div class="relative z-10 w-full max-w-sm mx-4">
            <div class="pemira-card rounded-2xl shadow-2xl p-8 backdrop-blur-md relative overflow-hidden"
                style="box-shadow: 0 0 60px rgba(201,162,39,0.1), 0 20px 60px rgba(0,0,0,0.5);">

                <img src="/Group 187.png" class="absolute top-0 left-0 w-16 opacity-70 mix-blend-screen pointer-events-none" alt="">
                <img src="/Group 187.png" class="absolute bottom-0 right-0 w-16 opacity-70 mix-blend-screen pointer-events-none rotate-180" alt="">

                <div class="relative z-10">
                    <div class="flex flex-col items-center gap-3 mb-6 mt-2">
                        <Link :href="route('home')" class="flex flex-col items-center gap-2">
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

                    <slot />
                </div>
            </div>
        </div>

        <div class="relative z-10 mt-6 text-center">
            <p class="text-xs text-white/20">
                PEMIRA LM FMIPA {{ dayjs().year() }} · Satu Suara untuk Masa Depan
            </p>
        </div>
    </div>
</template>
