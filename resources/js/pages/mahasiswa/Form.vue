<script setup lang="ts">
import { ref } from 'vue';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/vue3';
import { ProgramStudi, User } from '@/types';
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
    mahasiswaData?: User;
}>();

// define emits
const emit = defineEmits<{
    success: []
}>();

// define form fields
const form = useForm({
    nim: props.mahasiswaData?.nim || '',
    nama: props.mahasiswaData?.nama || '',
    email: props.mahasiswaData?.email || '',
    id_program_studi: props.mahasiswaData?.id_program_studi || 1,
    angkatan: props.mahasiswaData?.angkatan || new Date().getFullYear(),
    avatar: null as File | null,
});

// Avatar preview state
const avatarPreview = ref<string | null>(null);

// Handle avatar change
const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

// submit function
const submit = () => {
    switch (props.mode) {
        case 'create':
            form.post(route('users.store'), {
                forceFormData: true,
                onSuccess: () => {
                    emit('success');
                    form.reset();
                    avatarPreview.value = null;
                },
            });
            break;
        case 'edit':
            form.transform((data) => ({
                ...data,
                _method: 'PUT'
            })).post(route('users.update', props.mahasiswaData?.nim), {
                forceFormData: true,
                onSuccess: () => {
                    emit('success');
                    avatarPreview.value = null;
                },
            });
            break;
    }
};
</script>

<template>
    <DialogContent class="flex flex-col max-w-4xl max-h-[90dvh]">
        <!-- Dialog Header -->
        <DialogHeader>
            <DialogTitle>
                {{ mode === 'create' ? 'Tambah' : mode === 'edit' ? 'Ubah' : 'Detail' }} Data Mahasiswa
            </DialogTitle>
            <DialogDescription>
                {{ mode === 'create'
                    ? 'Isi form untuk menambahkan data mahasiswa baru.'
                    : mode === 'edit'
                    ? 'Ubah form untuk mengubah data mahasiswa.'
                    : 'Lihat detail data mahasiswa yang dipilih.'
                }}
            </DialogDescription>
        </DialogHeader>

        <!-- Form Content -->
        <form method="POST" @submit.prevent="submit" class="flex-1 overflow-y-auto">
            <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 items-start gap-4">
                <div class="grid grid-cols-2 items-start gap-4">
                    <!-- NIM -->
                    <div class="grid col-span-2 gap-2">
                        <Label for="nim">
                            NIM
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input id="nim" type="text" :tabindex="1" v-model="form.nim" :required="mode !== 'view'"
                            :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'"
                            placeholder="Masukkan NIM Mahasiswa" />
                        <InputError :message="form.errors.nim" />
                    </div>

                    <!-- Nama Lengkap -->
                    <div class="grid col-span-2 gap-2">
                        <Label for="nama">
                            Nama Lengkap
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input id="nama" type="text" :tabindex="2" v-model="form.nama" :required="mode !== 'view'"
                            :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'"
                            placeholder="Masukkan Nama Lengkap Mahasiswa" />
                        <InputError :message="form.errors.nama" />
                    </div>

                    <!-- Email -->
                    <div class="grid col-span-2 gap-2">
                        <Label for="email">
                            Email
                            <span v-if="mode === 'edit' && props.mahasiswaData?.email_verified_at !== null"
                                class='text-red-500'>*</span>
                        </Label>
                        <Input id="email" type="email" :tabindex="3" v-model="form.email"
                            :required="mode === 'view' && props.mahasiswaData?.email_verified_at !== null"
                            :disabled="form.processing || mode === 'view'" :readonly="mode === 'view'"
                            placeholder="Masukkan Email Mahasiswa" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <!-- Program Studi -->
                    <div class="grid col-span-1 gap-2">
                        <Label for="id_program_studi" :disabled="mode === 'view'">
                            Program Studi
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Select v-model="form.id_program_studi" :tabindex="4"
                            :disabled="form.processing || mode === 'view'" :required="mode !== 'view'">
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

                    <!-- Angkatan -->
                    <div class="grid col-span-1 gap-2">
                        <Label for="angkatan">
                            Angkatan
                            <span v-if="mode !== 'view'" class='text-red-500'>*</span>
                        </Label>
                        <Input id="angkatan" type="number" :tabindex="5" v-model="form.angkatan"
                            :required="mode !== 'view'" :disabled="form.processing || mode === 'view'"
                            :readonly="mode === 'view'" placeholder="Masukkan Angkatan" />
                        <InputError :message="form.errors.angkatan" />
                    </div>
                </div>

                <!-- Avatar Upload and Preview -->
                <div class="grid grid-cols-2 items-start gap-4">
                    <div class="grid col-span-2 items-start gap-2">
                        <Label for="avatar">
                            Foto Profil
                        </Label>
                        <Input id="avatar" type="file" accept="image/png, image/jpeg, image/jpg, image/webp"
                            :tabindex="6" @change="handleAvatarChange" :disabled="form.processing || mode === 'view'"
                            :readonly="mode === 'view'"
                            :class="cn(form.errors.avatar ? 'border-red-500' : '', 'w-full')" />
                        <InputError :message="form.errors.avatar" />
                    </div>
                    <div class="grid col-span-2 gap-4">
                        <AspectRatio :ratio="16 / 8" class="relative">
                            <img v-if="avatarPreview !== null" :src="avatarPreview" alt="Avatar Preview"
                                class="aspect-video h-full w-full border rounded-md object-contain" />
                            <img v-else :src="mahasiswaData?.avatar
                                ? `/storage/${mahasiswaData.avatar}`
                                : '/images/blank-profile-picture.webp'" alt="Avatar" :class="cn(
                                    form.errors.avatar ? 'border-red-500' : '',
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