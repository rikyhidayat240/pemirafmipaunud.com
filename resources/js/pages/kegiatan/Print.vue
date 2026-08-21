<script setup lang="ts">
import { Kegiatan } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Label } from '@/components/ui/label';
import { LoaderCircle } from 'lucide-vue-next';
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

// define form fields
const form = useForm({
    id_kegiatan: null as number | null,
});

// filter data for kegiatan fakultas only
const kegiatanFakultas = props.data.filter(kegiatan => kegiatan.ruang_lingkup === 'fakultas');

// submit function - open PDF in new tab
const submit = () => {
    if (form.id_kegiatan === null) {
        return;
    }

    // Open PDF in new tab
    window.open(route('events.print', form.id_kegiatan), '_blank');
    
    // Emit success and reset form
    emit('success');
    form.reset();
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