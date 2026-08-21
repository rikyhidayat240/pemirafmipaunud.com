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
            }
        }" />
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent class="mb-8">
            <slot />
        </AppContent>
        <AppFooter />
        <Dialog :open="!!alert" @update:open="opened => !opened ? alert = null : ''">
            <DialogContent>
                <DialogHeader>
                    <div v-if="alert?.type == 'error'"
                        class="size-16 rounded-full flex justify-center items-center border-4 border-red-700 text-red-700 mx-auto">
                        <X class="size-10" />
                    </div>
                    <TriangleAlert v-if="alert?.type == 'warning'" class="size-16 mx-auto text-yellow-700" />
                    <div v-if="alert?.type == 'success'"
                        class="size-16 rounded-full flex justify-center items-center border-4 border-green-700 text-green-700 mx-auto">
                        <Check class="size-10" />
                    </div>
                    <DialogTitle class="mt-2 text-xl text-center">{{ alert?.title }}</DialogTitle>
                    <DialogDescription class="text-base text-center">
                        {{ alert?.message }}
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button variant="outline" @click="() => alert = null">Keluar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppShell>
</template>
