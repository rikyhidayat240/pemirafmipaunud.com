<script setup lang="ts">
import { ref } from 'vue';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/vue3';
import { ProgramStudi, Kegiatan } from '@/types';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LoaderCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { DialogDescription, DialogHeader, DialogTitle, DialogContent } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// define props
const props = defineProps<{
    mode: 'view' | 'create' | 'edit';
    programStudi: ProgramStudi[];
    kegiatanData?: Kegiatan;
}>();

// define emits
const emit = defineEmits<{
    success: []
}>();

// define form fields
const form = useForm({
    id: props.kegiatanData?.id || null,
    nama: props.kegiatanData?.nama || '',
    ruang_lingkup: props.kegiatanData?.ruang_lingkup || 'fakultas',
    id_program_studi: props.kegiatanData?.id_program_studi || null,
    tahun: props.kegiatanData?.tahun || new Date().getFullYear(),
    waktu_mulai: props.kegiatanData?.waktu_mulai || '',
    waktu_selesai: props.kegiatanData?.waktu_selesai || '',
    foto: null as File | null,
});

// Helper function to parse datetime string
const parseDatetime = (datetime: string | Date | null | undefined): { date: string; time: string } => {
    if (!datetime) return { date: '', time: '' };
    
    const date = new Date(datetime);
    if (isNaN(date.getTime())) return { date: '', time: '' };
    
    // Format date as YYYY-MM-DD
    const dateStr = date.toISOString().split('T')[0];
    
    // Format time as HH:MM (local time)
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const timeStr = `${hours}:${minutes}`;
    
    return { date: dateStr, time: timeStr };
};

// Separate date and time inputs
const waktuMulaiParsed = parseDatetime(props.kegiatanData?.waktu_mulai);
const waktuMulaiDate = ref(waktuMulaiParsed.date);
const waktuMulaiTime = ref(waktuMulaiParsed.time);

const waktuSelesaiParsed = parseDatetime(props.kegiatanData?.waktu_selesai);
const waktuSelesaiDate = ref(waktuSelesaiParsed.date);
const waktuSelesaiTime = ref(waktuSelesaiParsed.time);

// Update waktu_mulai when date or time changes
const updateWaktuMulai = () => {
    if (waktuMulaiDate.value && waktuMulaiTime.value) {
        form.waktu_mulai = `${waktuMulaiDate.value} ${waktuMulaiTime.value}:00`;
    }
};

// Update waktu_selesai when date or time changes
const updateWaktuSelesai = () => {
    if (waktuSelesaiDate.value && waktuSelesaiTime.value) {
        form.waktu_selesai = `${waktuSelesaiDate.value} ${waktuSelesaiTime.value}:00`;
    }
};

// Foto preview state
const fotoPreview = ref<string | null>(null);

// Handle foto change
const handleFotoChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.foto = file;
        fotoPreview.value = URL.createObjectURL(file);
    }
};

// submit function
const submit = () => {
    switch (props.mode) {
        case 'create':
            form.post(route('events.store'), {
                forceFormData: true,
                onSuccess: () => {
                    emit('success');
                    form.reset();
                    fotoPreview.value = null;
                },
            });
            break;
        case 'edit':
            form.transform((data) => ({
                ...data,
                _method: 'PUT'
            })).post(route('events.update', props.kegiatanData?.id), {
                forceFormData: true,
                onSuccess: () => {
                    emit('success');
                    fotoPreview.value = null;
                },
            });
            break;
    }
};

</script>

<template>
    <DialogContent class="flex flex-col max-w-4xl max-h-[90dvh]">
        <!-- Dialog Header -->
        <DialogHeader class="shrink-0">
            <DialogTitle>
                {{ mode === 'create' ? 'Tambah' : mode === 'edit' ? 'Ubah' : 'Detail' }} Data Kegiatan
            </DialogTitle>
            <DialogDescription>
                {{ mode === 'create'
                    ? 'Isi form untuk menambahkan data kegiatan baru.'
                    : mode === 'edit'
                        ? 'Ubah form untuk mengubah data kegiatan.'
                        : 'Lihat detail data kegiatan yang dipilih.'
                }}
            </DialogDescription>
        </DialogHeader>

        <!-- Form Content -->
        <form method="POST" @submit.prevent="submit" class="flex-1 overflow-y-auto">
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 items-start gap-4">
                <div class="grid grid-cols-2 items-start gap-4">
                    <!-- Nama Kegiatan -->
                    <div class="grid col-span-2 gap-2">
                        <Label for="nama">
                            Nama Kegiatan
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input id="nama" type="text" :tabindex="1" v-model="form.nama" :required="mode !== 'view'"
                            :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'"
                            placeholder="Masukkan Nama Kegiatan" />
                        <InputError :message="form.errors.nama" />
                    </div>

                    <!-- Ruang Lingkup -->
                    <div class="grid col-span-2 gap-2">
                        <Label for="ruang_lingkup">
                            Ruang Lingkup
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Select v-model="form.ruang_lingkup" :tabindex="2"
                            :disabled="form.processing || mode === 'view'" :required="mode !== 'view'"
                            @update:model-value="(value) => { if (value === 'fakultas') form.id_program_studi = null; }">
                            <SelectTrigger id="ruang_lingkup">
                                <SelectValue placeholder="Pilih Ruang Lingkup" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="fakultas">Fakultas</SelectItem>
                                <SelectItem value="program studi">Program Studi</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.ruang_lingkup" />
                    </div>

                    <!-- Program Studi -->
                    <div class="grid col-span-1 gap-2">
                        <Label for="id_program_studi" :disabled="mode === 'view'">
                            Program Studi
                            <span v-if="mode !== 'view' && form.ruang_lingkup === 'program studi'"
                                class='text-red-500'>*</span>
                        </Label>
                        <Select v-model="form.id_program_studi" :tabindex="3"
                            :disabled="form.processing || mode === 'view' || form.ruang_lingkup === 'fakultas'"
                            :required="mode !== 'view' && form.ruang_lingkup === 'program studi'">
                            <SelectTrigger id="id_program_studi">
                                <SelectValue placeholder="Pilih Program Studi" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="ps in programStudi" :key="ps.id" :value="ps.id">
                                    {{ ps.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.id_program_studi" />
                    </div>

                    <!-- Tahun -->
                    <div class="grid col-span-1 gap-2">
                        <Label for="tahun">
                            Tahun
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input id="tahun" type="number" :tabindex="4" v-model="form.tahun" :required="mode !== 'view'"
                            :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'"
                            placeholder="Masukkan Tahun" />
                        <InputError :message="form.errors.tahun" />
                    </div>

                    <!-- Waktu Mulai -->
                    <div class="grid col-span-2 gap-2">
                        <Label for="waktu_mulai_date">
                            Waktu Mulai
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <div class="grid grid-cols-2 gap-2">
                            <Input id="waktu_mulai_date" type="date" :tabindex="5" v-model="waktuMulaiDate"
                                @input="updateWaktuMulai" :required="mode !== 'view'"
                                :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'" />
                            <Input id="waktu_mulai_time" type="time" :tabindex="6" v-model="waktuMulaiTime"
                                @input="updateWaktuMulai" :required="mode !== 'view'"
                                :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'" />
                        </div>
                        <InputError :message="form.errors.waktu_mulai" />
                    </div>

                    <!-- Waktu Selesai -->
                    <div class="grid col-span-2 gap-2">
                        <Label for="waktu_selesai_date">
                            Waktu Selesai
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <div class="grid grid-cols-2 gap-2">
                            <Input id="waktu_selesai_date" type="date" :tabindex="7" v-model="waktuSelesaiDate"
                                @input="updateWaktuSelesai" :required="mode !== 'view'"
                                :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'" />
                            <Input id="waktu_selesai_time" type="time" :tabindex="8" v-model="waktuSelesaiTime"
                                @input="updateWaktuSelesai" :required="mode !== 'view'"
                                :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'" />
                        </div>
                        <InputError :message="form.errors.waktu_selesai" />
                    </div>
                </div>

                <!-- Foto Upload and Preview -->
                <div class="grid grid-cols-2 items-start overflow-y-auto gap-4">
                    <div class="grid col-span-2 items-start gap-2">
                        <Label for="foto">
                            Foto Kegiatan
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input id="foto" type="file" accept="image/png, image/jpeg, image/jpg, image/webp" :tabindex="9"
                            @change="handleFotoChange" :disabled="form.processing || mode === 'view'"
                            :readonly="mode === 'view'"
                            :class="cn(form.errors.foto ? 'border-red-500' : '', 'w-full')" />
                        <InputError :message="form.errors.foto" />
                    </div>
                    <div class="grid col-span-2 gap-4">
                        <AspectRatio :ratio="16 / 11" class="relative">
                            <img v-if="fotoPreview !== null" :src="fotoPreview" alt="Foto Preview"
                                class="aspect-video h-full w-full border rounded-md object-contain" />
                            <img v-else :src="kegiatanData?.foto
                                ? `/storage/${kegiatanData.foto}`
                                : '/images/blank-photo-icon.jpg'" alt="Foto" :class="cn(
                                        form.errors.foto ? 'border-red-500' : '',
                                        'aspect-video h-full w-full border rounded-md object-contain'
                                    )" />
                        </AspectRatio>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div v-if="mode !== 'view'" class="flex justify-end gap-2 mt-4">
                <Button type="submit" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ mode === 'create' ? 'Tambah' : 'Simpan' }}
                </Button>
            </div>
        </form>
    </DialogContent>
</template>