<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { Eye, EyeOff, User, Lock } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Login Pemilih" description="">

        <Head title="Masuk" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-400 bg-green-400/10 rounded-lg px-3 py-2">
            {{ status }}
        </div>

        <form method="POST" @submit.prevent="submit" class="flex flex-col gap-4">
            <!-- NIM / Email Field -->
            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-xs font-semibold text-white/50 uppercase tracking-widest">
                    Nomor Induk Mahasiswa (NIM)
                </label>
                <div class="relative">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-yellow-400/50">
                        <User class="size-4" />
                    </div>
                    <input
                        id="email"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="Contoh: 24056001"
                        class="w-full pl-9 pr-4 py-2.5 rounded-lg text-sm text-white/90 pemira-input focus:outline-none transition-all"
                    />
                </div>
                <InputError :message="form.errors.email" />
            </div>

            <!-- Password Field -->
            <div class="flex flex-col gap-1.5">
                <label for="password" class="text-xs font-semibold text-white/50 uppercase tracking-widest">
                    Password
                </label>
                <div class="relative">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-yellow-400/50">
                        <Lock class="size-4" />
                    </div>
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Masukkan Password"
                        class="w-full pl-9 pr-10 py-2.5 rounded-lg text-sm text-white/90 pemira-input focus:outline-none transition-all"
                    />
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-white/30 hover:text-yellow-400 transition-colors">
                        <Eye v-if="!showPassword" class="h-4 w-4" />
                        <EyeOff v-else class="h-4 w-4" />
                    </button>
                </div>
                <InputError :message="form.errors.password" />
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :tabindex="4"
                :disabled="form.processing"
                class="mt-2 w-full py-3 rounded-lg text-sm font-bold tracking-wide btn-gold flex items-center justify-center gap-2 disabled:opacity-60"
            >
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Masuk ke Bilik Suara ➜
            </button>

            <!-- Divider -->
            <div class="flex items-center gap-3 my-1">
                <div class="flex-1 h-px bg-white/10"></div>
                <span class="text-xs text-white/20"></span>
                <div class="flex-1 h-px bg-white/10"></div>
            </div>

            <!-- Bottom Links -->
            <div class="flex flex-col items-center gap-2 text-center">
                <TextLink :href="route('dashboard')" :tabindex="5" class="text-sm text-white/40 hover:text-white/70 transition-colors">
                    Kembali
                </TextLink>
                <span class="text-xs text-white/20">·</span>
                <div class="text-xs text-white/30">
                    Belum punya akun?
                    <TextLink :href="route('register')" :tabindex="6" class="text-yellow-400/60 hover:text-yellow-400 transition-colors">
                        Jangan Daftar Mulu
                    </TextLink>
                </div>
            </div>
        </form>
    </AuthBase>
</template>
