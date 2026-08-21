<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { Eye, EyeOff } from 'lucide-vue-next'

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
    <AuthBase title="Masuk ke Akun Anda" description="Masukkan email dan kata sandi Anda di bawah ini.">

        <Head title="Masuk" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form method="POST" @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Alamat Email
                        <span className='text-red-500'>*</span>
                    </Label>
                    <Input id="email" type="email" required autofocus :tabindex="1" autocomplete="email"
                        v-model="form.email" placeholder="Email@student.unud.ac.id" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">Kata Sandi
                            <span className='text-red-500'>*</span>
                        </Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm"
                            :tabindex="5">
                            Lupa kata sandi?
                        </TextLink>
                    </div>
                    <div class="relative">
                        <Input id="password" :type="showPassword ? 'text' : 'password'" required :tabindex="2" autocomplete="current-password"
                            v-model="form.password" placeholder="Kata sandi min. 8 karakter" />
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <Eye v-if="!showPassword" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                </div>

                <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Masuk
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Belum memiliki akun?
                <TextLink :href="route('register')" :tabindex="5">Daftar disini</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
