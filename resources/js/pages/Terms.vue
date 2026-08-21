<script setup lang="ts">
import { AlertDialog, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, Kegiatan } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { LoaderCircle, TriangleAlert } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import dayjs from 'dayjs';

// Page title and breadcrumbs
const page = usePage();
const auth = computed(() => page.props.auth);
const title = 'Syarat dan Ketentuan';
const isMoved = ref(false);

// define props
const props = defineProps<{
    kegiatan: Kegiatan[];
    waktu: Date | string;
}>();

// Add computed property to filter kegiatan
const filteredKegiatan = computed(() => {
    if (!auth.value.user) return [];

    return props.kegiatan.filter((item) => {
        // Always show fakultas level activities
        if (item.ruang_lingkup === 'fakultas') {
            return true;
        }
        // Show program studi level activities only for matching program studi
        if (item.ruang_lingkup === 'program studi') {
            return item.id_program_studi === auth.value.user.id_program_studi;
        }
        return false;
    });
});

// Countdown timer logic
const currentTime = ref(new Date());
let interval: number | null = null;

// Add a function to calculate time difference for individual activities
const getTimeUntilStart = (startTime: Date) => {
    const target = new Date(startTime);
    const now = currentTime.value;
    const diff = target.getTime() - now.getTime();

    if (diff <= 0) {
        return { text: 'Sedang berlangsung', expired: true };
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    let text = '';
    if (days > 0) {
        text = `${days} hari ${hours} jam`;
    } else if (hours > 0) {
        text = `${hours} jam ${minutes} menit`;
    } else {
        text = `${minutes} menit`;
    }

    return { text, expired: false };
};

const candidateLink = (nama: string) => {
    const formattedName = nama.toLowerCase().replace(/\s+/g, '-');
    return `/candidates/${formattedName}`;
};

// Set up interval to update current time every second
onMounted(() => {
    interval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (interval) {
        clearInterval(interval);
    }
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Syarat dan Ketentuan',
        href: '/terms',
    },
];
</script>

<template>

    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-2 overflow-x-hidden">
            <div class="relative flex flex-1 flex-col items-start justify-start">
                <div class="w-full flex justify-between items-start relative">
                    <img src="/images/corner-image.png" alt="" class="w-20 sm:w-40 lg:w-50">
                    <img src="/images/background-logo-dpm.png" alt="" class="h-20 sm:h-50 my-auto">
                    <img src="/images/corner-image.png" alt="" class="w-20 sm:w-40 lg:w-50 transform -scale-x-100">
                    <h1
                        class="text-xl sm:text-3xl lg:text-4xl font-bold text-center text-primary text-shadow-sm text-shadow-background uppercase absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2
                            drop-shadow-[0_2px_4px_rgba(255,255,255,0.8)] dark:drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                        Pemilihan Umum Raya Mahasiswa FMIPA {{ dayjs().year() }}
                    </h1>
                </div>

                <div class="mx-auto grid w-full max-w-7xl items-start gap-4 px-4 mt-12">
                    <h1 class="md:text-md font-semibold lg:text-xl">Syarat & Ketentuan :</h1>
                    <ol class="md:text-md ml-2 list-inside list-decimal space-y-2 text-sm md:ml-10 lg:text-lg">
                        <li>Mahasiswa/i yang dapat memilih adalah mahasiswa/i aktif program studi sarjana Fakultas MIPA Universitas Udayana.</li>
                        <li>Mahasiswa/i hanya bisa melakukan pemilihan sebanyak satu kali tanpa adanya pengulangan.</li>
                        <li>Mahasiswa/i diharapkan menggunakan hak pilihnya dan memilih dengan berlandaskan Luberjurdil.</li>
                        <li>Hasil pemilihan bersifat mutlak dan tidak dapat diganggu gugat, sesuai dengan aturan yang telah ditetapkan.</li>
                        <li>Setiap pelanggaran terhadap syarat dan ketentuan ini akan dikenakan sanksi sesuai dengan peraturan yang berlaku.</li>
                    </ol>
                </div>

                <div v-if="auth.user && filteredKegiatan.length > 0" class="w-full px-4">
                    <h1 class="text-lg md:text-xl lg:text-2xl mt-12 mb-6 font-bold text-center">
                        Kegiatan Yang Anda Dapat Ikuti
                    </h1>
                    <div class="md:max-w-7xl mb-6 w-full grid place-self-center auto-rows-min gap-4 md:grid-cols-2">
                        <Card v-for="item in filteredKegiatan" :key="item.id"
                            class="border shadow-sm shadow-foreground/10">
                            <CardHeader>
                                <img :src="`/storage/${item.foto}`" alt="" class="w-full h-64 object-cover rounded-md">
                            </CardHeader>
                            <CardContent class="space-y-2 px-6">
                                <CardTitle class="text-lg md:text-xl">{{ item.nama }}</CardTitle>
                                <CardDescription>
                                    {{ getTimeUntilStart(item.waktu_mulai as Date).expired ?
                                        getTimeUntilStart(item.waktu_mulai as Date).text :
                                        `Dimulai dalam ${getTimeUntilStart(item.waktu_mulai as Date).text}`
                                    }}
                                </CardDescription>
                            </CardContent>
                            <CardFooter>
                                <Link :href="candidateLink(item.nama)">
                                <Button variant="default" size="default" :disabled="isMoved" @click="isMoved = true">
                                    Lihat Kandidat
                                </Button>
                                </Link>
                            </CardFooter>
                        </Card>
                    </div>
                </div>

                <div class="mx-auto mt-10 max-w-md grid grid-cols-2 gap-4 px-4">
                    <Link :href="route('dashboard')" class="w-full col-span-1">
                    <Button variant="outline" size="lg" :disabled="isMoved" @click="isMoved = true"
                        class="border-primary border-2 text-base font-semibold text-primary hover:text-primary w-full">
                        Batal
                    </Button>
                    </Link>
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button variant="default" size="lg" class="text-base font-semibold" :disabled="isMoved">
                                Mulai Memilih
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <TriangleAlert class="size-16 text-red-700 mx-auto" />
                                <AlertDialogTitle class="mt-2 text-xl text-center">
                                    Perhatian
                                </AlertDialogTitle>
                                <AlertDialogDescription class="text-base text-center">
                                    Pemilihan hanya dapat dilakukan sekali dan tidak ada pengulangan, apabila anda ingin
                                    melanjutkan silahkan klik tombol "Lanjutkan"!
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter class="sm:justify-center">
                                <div class="grid w-full grid-cols-2 gap-3">
                                    <AlertDialogCancel :disabled="isMoved"
                                        class="m-0 border border-primary text-primary hover:bg-primary/10">
                                        Batal
                                    </AlertDialogCancel>
                                    <Button as-child :disabled="isMoved" @click="isMoved = true">
                                        <Link :href="route('vote.show')" class="bg-primary hover:bg-primary/90">
                                        <LoaderCircle v-if="isMoved" class="size-4 animate-spin" />
                                        Lanjutkan
                                        </Link>
                                    </Button>
                                </div>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
