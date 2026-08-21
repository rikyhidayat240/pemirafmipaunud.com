<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';
import axios from 'axios';

const showEmailPassword = ref(false);
const checkingStudent = ref(false);
const studentMessage = ref('');
const studentError = ref('');
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    nim: '',
    nama: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const checkStudent = async () => {
    if (!form.nim || !form.nama) {
        studentError.value = 'Masukkan NIM dan Nama lengkap Anda.';
        return;
    }

    checkingStudent.value = true;
    studentError.value = '';
    studentMessage.value = '';

    try {
        const response = await axios.post(route('check.student'), {
            nim: form.nim,
            nama: form.nama,
        });

        if (response.data.exists) {
            showEmailPassword.value = true;
            studentMessage.value = response.data.message;
        } else {
            studentError.value = response.data.message;
            showEmailPassword.value = false;
        }
    } catch (error: any) {
        if (error.response?.data?.errors) {
            const errors = error.response.data.errors;
            studentError.value = Object.values(errors).flat().join(', ');
        } else {
            studentError.value = 'Terjadi kesalahan saat memeriksa data mahasiswa.';
        }
        showEmailPassword.value = false;
    } finally {
        checkingStudent.value = false;
    }
};

const resetForm = () => {
    showEmailPassword.value = false;
    studentMessage.value = '';
    studentError.value = '';
    form.reset();
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Daftar Akun Baru" description="Masukkan detail Anda di bawah ini untuk membuat akun">

        <Head title="Daftar" />

        <form method="POST" @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Step 1: NIM and Nama -->
                <div class="grid gap-2">
                    <Label for="nim">NIM
                        <span className='text-red-500'>*</span>
                    </Label>
                    <Input id="nim" type="text" required autofocus :tabindex="1" autocomplete="nim" v-model="form.nim"
                        placeholder="10 Digit NIM sesuai IMISSU" :disabled="showEmailPassword" />
                    <InputError :message="form.errors.nim" />
                </div>

                <div class="grid gap-2">
                    <Label for="nama">Nama Lengkap
                        <span className='text-red-500'>*</span>
                    </Label>
                    <Input id="nama" type="text" required :tabindex="2" autocomplete="nama" v-model="form.nama"
                        placeholder="Nama Lengkap sesuai IMISSU" :disabled="showEmailPassword" />
                    <InputError :message="form.errors.nama" />
                </div>

                <!-- Check Student Button -->
                <div v-if="!showEmailPassword" class="grid gap-2">
                    <Button type="button" @click="checkStudent" :disabled="checkingStudent || !form.nim || !form.nama"
                        class="w-full">
                        <LoaderCircle v-if="checkingStudent" class="h-4 w-4 animate-spin" />
                        Verifikasi Data
                    </Button>

                    <div v-if="studentError" class="text-sm text-red-600">
                        {{ studentError }}
                    </div>

                    <div v-if="studentMessage" class="text-sm text-green-600">
                        {{ studentMessage }}
                    </div>
                </div>

                <!-- Step 2: Email and Password (shown after verification) -->
                <template v-if="showEmailPassword">
                    <div class="grid gap-2">
                        <Label for="email">Alamat Email
                            <span className='text-red-500'>*</span>
                        </Label>
                        <Input id="email" type="email" required :tabindex="3" autocomplete="email" v-model="form.email"
                            placeholder="Email@domain (.com atau .ac.id)" />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">Kata Sandi
                            <span className='text-red-500'>*</span>
                        </Label>
                        <div class="relative">
                            <Input id="password" :type="showPassword ? 'text' : 'password'" required :tabindex="4"
                                autocomplete="new-password" v-model="form.password"
                                placeholder="Kata sandi min. 8 karakter" class="pr-10" />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                <Eye v-if="!showPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Konfirmasi Kata Sandi
                            <span className='text-red-500'>*</span>
                        </Label>
                        <div class="relative">
                            <Input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'"
                                required :tabindex="5" autocomplete="new-password" v-model="form.password_confirmation"
                                placeholder="Konfirmasi Kata Sandi Anda" class="pr-10" />
                            <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                <Eye v-if="!showPasswordConfirmation" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex gap-2">
                        <Button type="button" variant="outline" @click="resetForm" class="flex-1">
                            Kembali
                        </Button>
                        <Button type="submit" class="flex-1" :tabindex="6" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            Buat Akun
                        </Button>
                    </div>
                </template>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Sudah memiliki akun?
                <TextLink :href="route('login')" class="underline underline-offset-4" :tabindex="6">Masuk disini
                </TextLink>
            </div>
        </form>
    </AuthBase>
</template>
