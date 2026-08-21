<script setup lang="ts">
import { ref, computed } from 'vue';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/vue3';
import { User, Kegiatan, Kandidat } from '@/types';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LoaderCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { AspectRatio } from '@/components/ui/aspect-ratio';
import { DialogDescription, DialogHeader, DialogTitle, DialogContent } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

// define props
const props = defineProps<{
    mode: 'view' | 'create' | 'edit';
    mahasiswa: User[];
    kegiatan: Kegiatan[];
    kandidat: Kandidat[];
    kandidatData?: Kandidat;
}>();

// define emits
const emit = defineEmits<{
    success: []
}>();

// define form fields
const form = useForm({
    id: props.kandidatData?.id || null,
    id_kegiatan: props.kandidatData?.id_kegiatan || null,
    no_urut: props.kandidatData?.no_urut || '',
    visi: props.kandidatData?.visi || '',
    misi: props.kandidatData?.misi || '',
    nim_ketua: props.kandidatData?.mahasiswa?.find(m => m.pivot.jabatan === 'ketua')?.nim || null,
    nim_wakil: props.kandidatData?.mahasiswa?.find(m => m.pivot.jabatan === 'wakil')?.nim || null,
    foto: null as File | null,
});

// State untuk search
const searchKetua = ref('');
const searchWakil = ref('');
const showKetuaSuggestions = ref(false);
const showWakilSuggestions = ref(false);

// Filter mahasiswa yang sudah menjadi kandidat di kegiatan yang sama
const availableMahasiswaForKetua = computed(() => {
    if (!form.id_kegiatan) return props.mahasiswa;

    const existingKandidatNim = props.kandidat
        .filter(k => k.id_kegiatan === form.id_kegiatan && k.id !== form.id)
        .flatMap(k => k.mahasiswa?.map(m => m.nim) || []);

    const idProdiSelected = props.kegiatan.find(k => k.id === form.id_kegiatan)?.id_program_studi;
    
    return props.mahasiswa.filter(m => !existingKandidatNim.includes(m.nim) && 
        (idProdiSelected ? m.id_program_studi === idProdiSelected : true)
    );
});

const availableMahasiswaForWakil = computed(() => {
    return availableMahasiswaForKetua.value.filter(m => m.nim !== form.nim_ketua);
});

// Filtered suggestions berdasarkan search
const filteredKetuaSuggestions = computed(() => {
    if (!searchKetua.value) return availableMahasiswaForKetua.value.filter(m => m.is_admin === 0 || m.is_admin === false);
    const search = searchKetua.value.toLowerCase();
    return availableMahasiswaForKetua.value.filter(m => 
        m.nama.toLowerCase().includes(search) || 
        m.nim.toLowerCase().includes(search)
    );
});

const filteredWakilSuggestions = computed(() => {
    if (!searchWakil.value) return availableMahasiswaForWakil.value.filter(m => m.is_admin === 0 || m.is_admin === false);
    const search = searchWakil.value.toLowerCase();
    return availableMahasiswaForWakil.value.filter(m => 
        m.nama.toLowerCase().includes(search) || 
        m.nim.toLowerCase().includes(search)
    );
});

// Computed property untuk cek apakah kegiatan adalah fakultas
const isKegiatanFakultas = computed(() => {
    if (!form.id_kegiatan) return false;
    const selectedKegiatan = props.kegiatan.find(k => k.id === form.id_kegiatan);
    return selectedKegiatan?.ruang_lingkup === 'fakultas';
});

// Reset wakil ketika kegiatan berubah dan bukan fakultas
const handleKegiatanChange = (value: any) => {
    form.id_kegiatan = value;
    const selectedKegiatan = props.kegiatan.find(k => k.id === value);
    if (selectedKegiatan?.ruang_lingkup !== 'fakultas') {
        form.nim_wakil = null;
        searchWakil.value = '';
    }
    form.nim_ketua = null;
    form.nim_wakil = null;
    searchKetua.value = '';
    searchWakil.value = '';
};

// Get mahasiswa name by NIM
const getMahasiswaName = (nim: string | null) => {
    if (!nim) return '';
    const mahasiswa = props.mahasiswa.find(m => m.nim === nim);
    return mahasiswa ? `${mahasiswa.nama} (${mahasiswa.nim})` : '';
};

// Handle selection
const selectKetua = (mahasiswa: User) => {
    form.nim_ketua = mahasiswa.nim;
    searchKetua.value = `${mahasiswa.nama} (${mahasiswa.nim})`;
    showKetuaSuggestions.value = false;
};

const selectWakil = (mahasiswa: User) => {
    form.nim_wakil = mahasiswa.nim;
    searchWakil.value = `${mahasiswa.nama} (${mahasiswa.nim})`;
    showWakilSuggestions.value = false;
};

// Handle blur with delay
const handleKetuaBlur = () => {
    window.setTimeout(() => {
        showKetuaSuggestions.value = false;
    }, 200);
};

const handleWakilBlur = () => {
    window.setTimeout(() => {
        showWakilSuggestions.value = false;
    }, 200);
};

// Initialize search values
if ((props.mode === 'edit' || props.mode === 'view') && props.kandidatData) {
    searchKetua.value = getMahasiswaName(form.nim_ketua);
    searchWakil.value = getMahasiswaName(form.nim_wakil);
}

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
    const transformedData = {
        ...form.data(),
        mahasiswa: [
            { nim: form.nim_ketua, jabatan: 'ketua' },
            ...(isKegiatanFakultas.value && form.nim_wakil ? [{ nim: form.nim_wakil, jabatan: 'wakil' }] : [])
        ].filter(m => m.nim)
    };

    switch (props.mode) {
        case 'create':
            form.transform(() => transformedData).post(route('candidates.store'), {
                forceFormData: true,
                onSuccess: () => {
                    emit('success');
                    form.reset();
                    fotoPreview.value = null;
                    searchKetua.value = '';
                    searchWakil.value = '';
                },
            });
            break;
        case 'edit':
            form.transform((data) => ({
                ...transformedData,
                _method: 'PUT'
            })).post(route('candidates.update', props.kandidatData?.id), {
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
    <DialogContent class="flex flex-col max-w-5xl max-h-[90dvh]">
        <!-- Dialog Header -->
        <DialogHeader>
            <DialogTitle>
                {{ mode === 'create' ? 'Tambah' : mode === 'edit' ? 'Ubah' : 'Detail' }} Data Kandidat
            </DialogTitle>
            <DialogDescription>
                {{ mode === 'create'
                    ? 'Isi form untuk menambahkan data kandidat baru.'
                    : mode === 'edit'
                    ? 'Ubah form untuk mengubah data kandidat.'
                    : 'Lihat detail data kandidat yang dipilih.'
                }}
            </DialogDescription>
        </DialogHeader>

        <!-- Form Content -->
        <form method="POST" @submit.prevent="submit" class="flex-1 overflow-y-auto">
            <div class="mt-2 grid sm:grid-cols-2 overflow-y-auto items-start gap-4">
                <div class="grid grid-cols-3 items-start gap-4">
                    <!-- Kegiatan -->
                    <div class="grid col-span-3 lg:col-span-2 gap-2">
                        <Label for="id_kegiatan">
                            Kegiatan
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Select :model-value="form.id_kegiatan" @update:model-value="handleKegiatanChange" :tabindex="1"
                            :disabled="form.processing || mode === 'view'" :required="mode !== 'view'">
                            <SelectTrigger id="id_kegiatan">
                                <SelectValue placeholder="Pilih Kegiatan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="k in kegiatan" :key="k.id" :value="k.id">
                                    {{ k.nama.replace('Kegiatan ', '') }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.id_kegiatan" />
                    </div>

                    <!-- Nomor Urut -->
                    <div class="grid col-span-3 lg:col-span-1 gap-2">
                        <Label for="no_urut">
                            No. Urut
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input id="no_urut" type="text" :tabindex="2" v-model="form.no_urut"
                            :required="mode !== 'view'" :disabled="form.processing || mode === 'view'"
                            :readonly="mode === 'view'" placeholder="Contoh: 01" />
                        <InputError :message="form.errors.no_urut" />
                    </div>

                    <!-- Ketua with Search Input -->
                    <div class="grid col-span-3 gap-2 relative">
                        <Label for="nim_ketua">
                            Ketua
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input 
                            id="nim_ketua" 
                            type="text" 
                            :tabindex="3" 
                            v-model="searchKetua"
                            @focus="showKetuaSuggestions = true"
                            @blur="handleKetuaBlur"
                            :disabled="form.processing || mode === 'view' || !form.id_kegiatan"
                            :readonly="mode === 'view'" 
                            placeholder="Cari mahasiswa..." 
                            autocomplete="off"
                        />
                        <!-- Suggestions Dropdown -->
                        <div 
                            v-if="showKetuaSuggestions && filteredKetuaSuggestions.length > 0 && mode !== 'view'"
                            class="absolute top-full left-0 right-0 mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-y-auto z-50"
                        >
                            <div
                                v-for="m in filteredKetuaSuggestions"
                                :key="m.nim"
                                @click="selectKetua(m)"
                                class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                            >
                                {{ m.nama }} ({{ m.nim }})
                            </div>
                        </div>
                        <p v-if="!form.id_kegiatan" class="text-xs text-muted-foreground">
                            Pilih kegiatan terlebih dahulu
                        </p>
                        <InputError :message="form.errors.nim_ketua" />
                    </div>

                    <!-- Wakil with Search Input (only for fakultas) -->
                    <div v-if="isKegiatanFakultas" class="grid col-span-3 gap-2 relative">
                        <Label for="nim_wakil">
                            Wakil Ketua
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input 
                            id="nim_wakil" 
                            type="text" 
                            :tabindex="4" 
                            v-model="searchWakil"
                            @focus="showWakilSuggestions = true"
                            @blur="handleWakilBlur"
                            :disabled="form.processing || mode === 'view' || !form.nim_ketua"
                            :readonly="mode === 'view'" 
                            placeholder="Cari mahasiswa..." 
                            autocomplete="off"
                        />
                        <!-- Suggestions Dropdown -->
                        <div 
                            v-if="showWakilSuggestions && filteredWakilSuggestions.length > 0 && mode !== 'view'"
                            class="absolute top-full left-0 right-0 mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-y-auto z-50"
                        >
                            <div
                                v-for="m in filteredWakilSuggestions"
                                :key="m.nim"
                                @click="selectWakil(m)"
                                class="px-3 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                            >
                                {{ m.nama }} ({{ m.nim }})
                            </div>
                        </div>
                        <p v-if="!form.nim_ketua" class="text-xs text-muted-foreground">
                            Pilih ketua terlebih dahulu
                        </p>
                        <InputError :message="form.errors.nim_wakil" />
                    </div>

                    <!-- Visi -->
                    <div class="grid col-span-3 gap-2">
                        <Label for="visi">
                            Visi
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Textarea id="visi" :tabindex="5" v-model="form.visi"
                            :required="mode !== 'view'" :disabled="form.processing || mode === 'view'"
                            :readonly="mode === 'view'" placeholder="Masukkan Visi" />
                        <InputError :message="form.errors.visi" />
                    </div>

                    <!-- Misi -->
                    <div class="grid col-span-3 gap-2">
                        <Label for="misi">
                            Misi
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Textarea id="misi" :tabindex="6" v-model="form.misi"
                            :required="mode !== 'view'" :disabled="form.processing || mode === 'view'"
                            :readonly="mode === 'view'" placeholder="Masukkan Misi"
                            class="h-32" />
                        <InputError :message="form.errors.misi" />
                    </div>
                </div>

                <!-- Foto Upload and Preview -->
                <div class="grid grid-cols-2 items-start gap-4">
                    <div class="grid col-span-2 items-start gap-2">
                        <Label for="foto">
                            Foto Kandidat
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input id="foto" type="file" accept="image/png, image/jpeg, image/jpg, image/webp"
                            :tabindex="7" @change="handleFotoChange" :disabled="form.processing || mode === 'view'"
                            :readonly="mode === 'view'"
                            :class="cn(form.errors.foto ? 'border-red-500' : '', 'w-full')" />
                        <InputError :message="form.errors.foto" />
                    </div>
                    <div class="grid col-span-2 gap-4">
                        <AspectRatio :ratio="16 / 14" class="relative">
                            <img v-if="fotoPreview !== null" :src="fotoPreview" alt="Foto Preview"
                                class="aspect-video h-full w-full border rounded-md object-contain" />
                            <img v-else :src="kandidatData?.foto
                                ? `/storage/${kandidatData.foto}`
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