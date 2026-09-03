<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff, User, Lock, Mail } from 'lucide-vue-next';
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
    <AuthBase title="Daftar Akun Baru" description="">

        <Head title="Daftar" />

        <form method="POST" @submit.prevent="submit" class="flex flex-col gap-4">
            <div class="flex flex-col gap-4">
                <!-- NIM Field -->
                <div class="flex flex-col gap-1.5">
                    <label for="nim" class="text-xs font-semibold text-white/50 uppercase tracking-widest">NIM</label>
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-yellow-400/50">
                            <User class="size-4" />
                        </div>
                        <input id="nim" type="text" required autofocus :tabindex="1" autocomplete="nim"
                            v-model="form.nim"
                            placeholder="10 Digit NIM sesuai IMISSU"
                            :disabled="showEmailPassword"
                            class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm text-white/90 pemira-input focus:outline-none transition-all disabled:opacity-50" />
                    </div>
                    <InputError :message="form.errors.nim" />
                </div>

                <!-- Nama Field -->
                <div class="flex flex-col gap-1.5">
                    <label for="nama" class="text-xs font-semibold text-white/50 uppercase tracking-widest">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-yellow-400/50">
                            <User class="size-4" />
                        </div>
                        <input id="nama" type="text" required :tabindex="2" autocomplete="nama"
                            v-model="form.nama"
                            placeholder="Nama Lengkap sesuai IMISSU"
                            :disabled="showEmailPassword"
                            class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm text-white/90 pemira-input focus:outline-none transition-all disabled:opacity-50" />
                    </div>
                    <InputError :message="form.errors.nama" />
                </div>

                <!-- Verify Step 1 -->
                <div v-if="!showEmailPassword" class="flex flex-col gap-2">
                    <button type="button" @click="checkStudent"
                        :disabled="checkingStudent || !form.nim || !form.nama"
                        class="w-full py-2.5 rounded-lg text-sm font-bold btn-gold disabled:opacity-60 flex items-center justify-center gap-2">
                        <LoaderCircle v-if="checkingStudent" class="h-4 w-4 animate-spin" />
                        Verifikasi Data
                    </button>

                    <p v-if="studentError" class="text-xs text-red-400 text-center">{{ studentError }}</p>
                    <p v-if="studentMessage" class="text-xs text-green-400 text-center">{{ studentMessage }}</p>
                </div>

                <!-- Step 2: Email and Password -->
                <template v-if="showEmailPassword">
                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="text-xs font-semibold text-white/50 uppercase tracking-widest">Alamat Email</label>
                        <div class="relative">
                            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-yellow-400/50">
                                <Mail class="size-4" />
                            </div>
                            <input id="email" type="email" required :tabindex="3" autocomplete="email"
                                v-model="form.email"
                                placeholder="Email@domain (.com atau .ac.id)"
                                class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm text-white/90 pemira-input focus:outline-none transition-all" />
                        </div>
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-xs font-semibold text-white/50 uppercase tracking-widest">Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-yellow-400/50">
                                <Lock class="size-4" />
                            </div>
                            <input id="password" :type="showPassword ? 'text' : 'password'" required :tabindex="4"
                                autocomplete="new-password" v-model="form.password"
                                placeholder="Kata sandi min. 8 karakter"
                                class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm text-white/90 pemira-input focus:outline-none transition-all" />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-yellow-400 transition-colors">
                                <Eye v-if="!showPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password_confirmation" class="text-xs font-semibold text-white/50 uppercase tracking-widest">Konfirmasi Kata Sandi</label>
                        <div class="relative">
                            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-yellow-400/50">
                                <Lock class="size-4" />
                            </div>
                            <input id="password_confirmation" :type="showPasswordConfirmation ? 'text' : 'password'"
                                required :tabindex="5" autocomplete="new-password" v-model="form.password_confirmation"
                                placeholder="Konfirmasi Kata Sandi Anda"
                                class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm text-white/90 pemira-input focus:outline-none transition-all" />
                            <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-yellow-400 transition-colors">
                                <Eye v-if="!showPasswordConfirmation" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="resetForm"
                            class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white/50 border border-white/15 hover:border-yellow-400/30 hover:text-yellow-400 transition-all">
                            Kembali
                        </button>
                        <button type="submit" class="flex-1 py-2.5 rounded-lg text-sm font-bold btn-gold flex items-center justify-center gap-2 disabled:opacity-60"
                            :tabindex="6" :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            Buat Akun
                        </button>
                    </div>
                </template>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex-1 h-px bg-white/10"></div>
                <span class="text-xs text-white/20"></span>
                <div class="flex-1 h-px bg-white/10"></div>
            </div>

            <div class="text-center text-xs text-white/30">
                Sudah memiliki akun?
                <TextLink :href="route('login')" class="text-yellow-400/60 hover:text-yellow-400 transition-colors underline-offset-4" :tabindex="7">
                    Masuk disini
                </TextLink>
            </div>
        </form>
    </AuthBase>
</template>
