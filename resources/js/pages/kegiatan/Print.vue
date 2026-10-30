<script setup lang="ts">
import { Kegiatan } from '@/types';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { DialogDescription, DialogHeader, DialogTitle, DialogContent } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// define props
const props = defineProps<{
    data: Kegiatan[];
}>();

// define emits
const emit = defineEmits<{
    success: []
}>();

// filter data for latest kegiatan fakultas only (props.data is ordered by tahun desc, created_at desc)
const kegiatanFakultas = computed(() =>
    props.data.filter(kegiatan => kegiatan.ruang_lingkup === 'fakultas').slice(0, 1)
);

// define form fields
const form = useForm({
    id_kegiatan: (kegiatanFakultas.value[0]?.id ?? null) as number | null,
});

// submit function - open PDF in new tab
const submit = () => {
    const selectedId = form.id_kegiatan ?? kegiatanFakultas.value[0]?.id ?? null;
    if (selectedId === null) {
        return;
    }

    // Open PDF in new tab
    window.open(route('events.print', selectedId), '_blank');
    
    // Emit success and reset form
    emit('success');
    form.id_kegiatan = kegiatanFakultas.value[0]?.id ?? null;
}
</script>

<template>
    <DialogContent class="flex flex-col max-h-[90dvh]">
        <!-- Dialog Header -->
        <DialogHeader>
            <DialogTitle>Unduh Presensi Mahasiswa</DialogTitle>
            <DialogDescription>
                Unduh daftar presensi mahasiswa dalam format PDF untuk keperluan dokumentasi
            </DialogDescription>
        </DialogHeader>

        <!-- Form Content -->
        <form method="POST" @submit.prevent="submit" class="flex-1 overflow-y-auto">
            <div class="mt-2 grid grid-cols-1 items-start gap-4">
                <!-- Kegiatan Selection -->
                <div class="grid col-span-1 gap-2">
                    <Label for="id_kegiatan">
                        Kegiatan
                        <span class='text-red-500'>*</span>
                    </Label>
                    <Select v-model="form.id_kegiatan" :tabindex="1">
                        <SelectTrigger id="id_kegiatan" class="w-full">
                            <SelectValue placeholder="Pilih Kegiatan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="kegiatan in kegiatanFakultas" :key="kegiatan.id" :value="kegiatan.id">
                                {{ kegiatan.nama.replace('Ketua dan Wakil Ketua BEM', 'Umum Raya') }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-2 mt-4">
                <Button type="submit" :disabled="form.id_kegiatan === null">
                    Unduh
                </Button>
            </div>
        </form>
    </DialogContent>
</template>