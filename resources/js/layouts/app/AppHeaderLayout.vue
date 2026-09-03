<script setup lang="ts">
import { ref } from 'vue';
import { watch } from 'vue';
import 'vue-sonner/style.css';
import { usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppShell from '@/components/AppShell.vue';
import { Toaster } from '@/components/ui/sonner';
import AppHeader from '@/components/AppHeader.vue';
import AppFooter from '@/components/AppFooter.vue';
import type { BreadcrumbItemType } from '@/types';
import AppContent from '@/components/AppContent.vue';
import { Check, TriangleAlert, X } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const alert = ref(page.props.alert)

watch(() => page.props.alert, (newFlash) => {
  alert.value = newFlash
}, { immediate: true, deep: true });
</script>

<template>
    <AppShell variant="header">
        <Toaster position="top-right" :expand="true" rich-colors :toast-options="{
            duration: 3000,
            style: {
                zIndex: '3000',
                fontFamily: 'var(--font-sans)',
                fontSize: 'var(--text-sm)',
                background: '#1a1f4a',
                border: '1px solid rgba(201,162,39,0.3)',
                color: '#e8e8f0',
            }
        }" />

        <!-- Star background overlay using Figma Assets -->
        <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden flex justify-center">
            <div class="relative w-full h-full max-w-[1920px]">
                <!-- Gold constellation (Group 186) -->
                <img src="/Group 186.png" class="absolute top-[15%] left-[3%] w-[20vw] max-w-[150px] min-w-[80px] opacity-60 animate-pulse" style="animation-duration: 4s;" alt="">

                <!-- Big 4-pointed star (Group 220) -->
                <img src="/Group 220.png" class="absolute top-[10%] right-[10%] w-[12vw] max-w-[100px] min-w-[50px] opacity-50" style="animation: float-star 8s ease-in-out infinite;" alt="">
                <img src="/Group 220.png" class="absolute bottom-[30%] left-[8%] w-[8vw] max-w-[80px] min-w-[40px] opacity-40" style="animation: float-star 6s ease-in-out infinite 2s; transform: scaleX(-1);" alt="">

                <!-- Static decorative stars -->
                <div class="absolute top-[8%] left-[12%] size-1 rounded-full bg-yellow-400/40" style="animation: twinkle 3s ease-in-out infinite;"></div>
                <div class="absolute top-[15%] left-[25%] size-0.5 rounded-full bg-white/30" style="animation: twinkle 4s ease-in-out infinite 0.5s;"></div>
                <div class="absolute top-[22%] left-[45%] size-1 rounded-full bg-white/20" style="animation: twinkle 5s ease-in-out infinite 1s;"></div>
                <div class="absolute top-[30%] right-[15%] size-0.5 rounded-full bg-white/25" style="animation: twinkle 4.5s ease-in-out infinite 1.2s;"></div>
                <div class="absolute top-[55%] right-[8%] size-1 rounded-full bg-white/15" style="animation: twinkle 5.5s ease-in-out infinite 0.6s;"></div>
                <div class="absolute top-[85%] left-[35%] size-0.5 rounded-full bg-yellow-400/15" style="animation: twinkle 5.8s ease-in-out infinite 1.8s;"></div>
                <div class="absolute top-[90%] right-[10%] size-1 rounded-full bg-white/20" style="animation: twinkle 4.8s ease-in-out infinite 0.4s;"></div>
            </div>
        </div>

        <div class="relative z-10 flex min-h-screen flex-col">
            <AppHeader :breadcrumbs="breadcrumbs" />
            <AppContent class="mb-0 flex-1">
                <slot />
            </AppContent>
            <AppFooter />
        </div>

        <Dialog :open="!!alert" @update:open="opened => !opened ? alert = null : ''">
            <DialogContent class="bg-[#1a1f4a] border-yellow-900/30">
                <DialogHeader>
                    <div v-if="alert?.type == 'error'"
                        class="size-16 rounded-full flex justify-center items-center border-4 border-red-500 text-red-500 mx-auto">
                        <X class="size-10" />
                    </div>
                    <TriangleAlert v-if="alert?.type == 'warning'" class="size-16 mx-auto text-yellow-400" />
                    <div v-if="alert?.type == 'success'"
                        class="size-16 rounded-full flex justify-center items-center border-4 border-green-500 text-green-500 mx-auto">
                        <Check class="size-10" />
                    </div>
                    <DialogTitle class="mt-2 text-xl text-center text-white">{{ alert?.title }}</DialogTitle>
                    <DialogDescription class="text-base text-center text-white/60">
                        {{ alert?.message }}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" @click="() => alert = null" class="border-yellow-400/30 text-yellow-400 hover:bg-transparent dark:hover:bg-transparent hover:underline bg-transparent">Keluar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppShell>
</template>
