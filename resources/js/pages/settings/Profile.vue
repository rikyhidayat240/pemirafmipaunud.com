<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { FlashMessage, type BreadcrumbItem, type User } from '@/types';
import { LoaderCircle } from 'lucide-vue-next';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    flash?: FlashMessage;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    success: []
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Pengaturan Profil',
        href: '/settings/profile',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

const form = useForm({
    nama: user.nama,
    email: user.email,
    avatar: null as File | null,
});

const avatarPreview = ref<string | null>(null);

const handleAvatarChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
    })).post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            emit('success');
            avatarPreview.value = null;
        },
    });
};

watch(() => props.flash, (newFlash) => {
    if (newFlash?.success) {
        toast.success(newFlash.success);
    }
    if (newFlash?.error) {
        toast.error(newFlash.error);
    }
}, { immediate: true, deep: true });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Pengaturan Profil" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Informasi Profil" description="Perbarui nama, foto, dan alamat email Anda" />

                <form method="POST" @submit.prevent="submit" class="space-y-6">
                    <!-- Avatar Upload and Preview -->
                    <div class="grid grid-cols-2 items-start gap-4">
                        <div class="grid col-span-2 items-start gap-2">
                            <Label for="avatar">
                                Foto Profil
                            </Label>
                            <Input id="avatar" type="file" accept="image/png, image/jpeg, image/jpg, image/webp"
                                @change="handleAvatarChange" :disabled="form.processing"
                                :class="cn(form.errors.avatar ? 'border-red-500' : '', 'w-full')" />
                            <InputError :message="form.errors.avatar" />
                        </div>
                        <div class="grid col-span-2 gap-4">
                            <AspectRatio :ratio="16 / 8" class="relative">
                                <img v-if="avatarPreview !== null" :src="avatarPreview" alt="Avatar Preview"
                                    class="aspect-video h-full w-full border rounded-md object-contain" />
                                <img v-else :src="user?.avatar
                                    ? `/storage/${user.avatar}`
                                    : '/images/blank-profile-picture.webp'" alt="Avatar" :class="cn(
                                        form.errors.avatar ? 'border-red-500' : '',
                                        'aspect-video h-full w-full border rounded-md object-contain'
                                    )" />
                            </AspectRatio>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Nama lengkap
                            <span class="text-red-500">*</span>
                        </Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.nama" required
                            placeholder="Masukkan Nama Lengkap Anda" />
                        <InputError class="mt-2" :message="form.errors.nama" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email
                            <span class="text-red-500">*</span>
                        </Label>
                        <Input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                            autocomplete="username" placeholder="Email address" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link :href="route('verification.send')" method="post" as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500">
                            Click here to resend the verification email.
                            </Link>
                        </p>

                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">
                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                            Simpan
                        </Button>

                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Tersimpan.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
