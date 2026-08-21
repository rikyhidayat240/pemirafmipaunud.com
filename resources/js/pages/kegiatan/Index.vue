<script setup lang="ts">
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import { columns } from './columns';
import { Head } from '@inertiajs/vue3';
import DataTable from './DataTable.vue';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import type { ProgramStudi, FlashMessage, Kegiatan } from '@/types';

const props = defineProps<{
    kegiatan: Kegiatan[];
    programStudi: ProgramStudi[];
    flash?: FlashMessage;
}>();

watch(() => props.flash, (newFlash) => {
    if (newFlash?.success) {
        toast.success(newFlash.success);
    }
    if (newFlash?.error) {
        toast.error(newFlash.error);
    }
}, { immediate: true, deep: true });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kegiatan',
        href: '/events',
    },
];
</script>

<template>
    <Head title="Kegiatan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="w-full md:max-w-7xl mx-auto px-4 py-6">
            <DataTable 
                :columns="columns" 
                :data="kegiatan" 
                :helpers="{
                    programStudi: programStudi
                }" 
            />
        </div>
    </AppLayout>
</template>