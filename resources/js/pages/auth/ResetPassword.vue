<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthLayout title="Reset Kata Sandi" description="Silakan masukkan kata sandi baru Anda di bawah ini">

        <Head title="Reset Kata Sandi" />

        <form method="POST" @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Alamat Email
                        <span class="text-red-500">*</span>
                    </Label>
                    <Input id="email" type="email" name="email" autocomplete="email" v-model="form.email"
                        class="mt-1 block w-full" readonly />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Kata Sandi
                        <span className='text-red-500'>*</span>
                    </Label>
                    <div class="relative">
                        <Input id="password" :type="showPassword ? 'text' : 'password'" required
                            autocomplete="new-password" v-model="form.password" placeholder="Kata sandi min. 8 karakter"
                            class="pr-10" />
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
                            required autocomplete="new-password" v-model="form.password_confirmation"
                            placeholder="Konfirmasi Kata Sandi Anda" class="pr-10" />
                        <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                            <Eye v-if="!showPasswordConfirmation" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <Button type="submit" class="mt-4 w-full" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Reset Kata Sandi
                </Button>
            </div>
        </form>
    </AuthLayout>
</template>
