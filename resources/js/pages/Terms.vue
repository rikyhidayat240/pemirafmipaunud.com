<script setup lang="ts">
import { AlertDialog, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, Kegiatan } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { LoaderCircle, TriangleAlert, ChevronRight } from 'lucide-vue-next';
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
        if (item.ruang_lingkup === 'fakultas') {
            return true;
        }
        if (item.ruang_lingkup === 'program studi') {
            return item.id_program_studi === auth.value.user.id_program_studi;
        }
        return false;
    });
});

// Countdown timer logic
const currentTime = ref(new Date());
let interval: number | null = null;

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

const terms = [
    'Mahasiswa/i yang dapat memilih adalah mahasiswa/i aktif program studi sarjana Fakultas MIPA.',
    'Mahasiswa/i hanya bisa melakukan pemilihan sebanyak satu kali tanpa adanya pengulangan.',
    'Mahasiswa/i diharapkan menggunakan hak pilinya dan memilih dengan berlandaskan Luberjurdil.',
    'Hasil pemilihan bersifat mutlak dan tidak dapat diganggu gugat, sesuai dengan aturan yang telah ditetapkan.',
    'Setiap pelanggaran terhadap syarat dan ketentuan ini akan dikenakan sanksi sesuai dengan peraturan yang berlaku.',
];
</script>

<template>

    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-0 overflow-x-hidden">

            <!-- ===== PAGE HEADER ===== -->
            <div class="relative flex items-center justify-center py-10 px-4 overflow-hidden">
                <!-- Corner ornaments -->
                <div class="absolute top-0 left-0 w-24 h-24 opacity-20"
                    style="background: linear-gradient(135deg, rgba(201,162,39,0.4) 0%, transparent 60%); clip-path: polygon(0 0, 100% 0, 0 100%);"></div>
                <div class="absolute top-0 right-0 w-24 h-24 opacity-20"
                    style="background: linear-gradient(225deg, rgba(201,162,39,0.4) 0%, transparent 60%); clip-path: polygon(0 0, 100% 0, 100% 100%);"></div>

                <div class="relative z-10 flex flex-col items-center gap-3 text-center">
                    <img src="/Logo pemira.png" alt="Logo PEMIRA"
                        class="size-16 drop-shadow-lg opacity-90"
                        onerror="this.style.display='none'" />
                    <div>
                        <p class="text-xs text-white/40 uppercase tracking-widest">Selamat Datang di Bilik Suara</p>
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-white uppercase poppins-font mt-1">
                            Pemilihan Umum Raya Mahasiswa
                        </h1>
                        <p class="text-lg sm:text-xl font-black poppins-font" style="color: #f0c040;">
                            FMIPA {{ dayjs().year() }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ===== SYARAT & KETENTUAN ===== -->
            <div class="max-w-4xl mx-auto w-full px-4 pb-8">
                <div class="pemira-card rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-1 h-6 rounded-full" style="background: #c9a227;"></div>
                        <h2 class="text-base font-bold text-white">Syarat & Ketentuan</h2>
                    </div>
                    <ol class="space-y-3">
                        <li v-for="(term, idx) in terms" :key="idx"
                            class="flex items-start gap-3 text-sm text-white/60 leading-relaxed">
                            <span class="flex-shrink-0 size-5 rounded-full border border-yellow-400/30 text-yellow-400 flex items-center justify-center text-xs font-semibold mt-0.5">
                                {{ idx + 1 }}
                            </span>
                            <span>{{ term }}</span>
                        </li>
                    </ol>
                </div>
            </div>

            <!-- ===== KEGIATAN SECTION ===== -->
            <div v-if="auth.user && filteredKegiatan.length > 0" class="max-w-4xl mx-auto w-full px-4 pb-8">
                <h2 class="text-base font-bold text-center text-white mb-5">
                    Kegiatan Yang Anda Dapat Ikuti
                </h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div v-for="item in filteredKegiatan" :key="item.id"
                        class="pemira-card rounded-xl overflow-hidden hover:border-yellow-400/30 transition-all duration-300">
                        <!-- Header with decorative background -->
                        <div class="relative p-4 pb-3" style="background: linear-gradient(135deg, #1e2456, #2a3070);">
                            <div class="absolute top-2 right-2 opacity-20">
                                <div class="star-4 size-6 bg-yellow-400"></div>
                            </div>
                            <p class="text-xs text-yellow-400/70 uppercase tracking-widest font-semibold">Pemilihan Umum Raya · FMIPA {{ dayjs().year() }}</p>
                            <h3 class="text-sm font-bold text-white mt-1 leading-tight">{{ item.nama }}</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex items-center gap-2">
                                <div class="size-1.5 rounded-full bg-green-400 animate-pulse"></div>
                                <p class="text-xs text-white/50">
                                    {{ getTimeUntilStart(item.waktu_mulai as Date).expired ?
                                        getTimeUntilStart(item.waktu_mulai as Date).text :
                                        `Dimulai dalam ${getTimeUntilStart(item.waktu_mulai as Date).text}`
                                    }}
                                </p>
                            </div>
                            <Link :href="candidateLink(item.nama)">
                                <button :disabled="isMoved" @click="isMoved = true"
                                    class="w-full py-2 rounded-lg text-xs font-semibold text-white/60 border border-white/10 hover:border-yellow-400/30 hover:text-yellow-400 transition-all">
                                    Lihat Kandidat
                                </button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== CTA BUTTONS ===== -->
            <div class="max-w-sm mx-auto w-full px-4 pb-12 grid grid-cols-2 gap-4">
                <Link :href="route('dashboard')" class="w-full">
                    <button :disabled="isMoved" @click="isMoved = true"
                        class="w-full py-3 rounded-full text-sm font-semibold text-white/50 border border-white/15 hover:border-yellow-400/30 hover:text-yellow-400 transition-all">
                        ← Kembali ke Beranda
                    </button>
                </Link>

                <AlertDialog>
                    <AlertDialogTrigger as-child>
                        <button :disabled="isMoved" class="btn-gold w-full py-3 text-sm font-bold">
                            Mulai Pemilihan ➜
                        </button>
                    </AlertDialogTrigger>
                    <AlertDialogContent class="bg-[#1a1f4a] border-yellow-900/30">
                        <AlertDialogHeader>
                            <TriangleAlert class="size-16 text-yellow-400 mx-auto" />
                            <AlertDialogTitle class="mt-2 text-xl text-center text-white">
                                Perhatian
                            </AlertDialogTitle>
                            <AlertDialogDescription class="text-base text-center text-white/60">
                                Pemilihan hanya dapat dilakukan sekali dan tidak ada pengulangan, apabila anda ingin
                                melanjutkan silahkan klik tombol "Lanjutkan"!
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter class="sm:justify-center">
                            <div class="grid w-full grid-cols-2 gap-3">
                                <AlertDialogCancel :disabled="isMoved"
                                    class="m-0 border border-yellow-400/30 text-yellow-400 hover:bg-transparent dark:hover:bg-transparent hover:underline bg-transparent">
                                    Batal
                                </AlertDialogCancel>
                                    <Link :href="route('vote.show')" @click="isMoved = true" :class="['btn-gold py-2 rounded-lg text-sm font-bold flex items-center justify-center gap-2', isMoved ? 'opacity-70 pointer-events-none' : '']">
                                        <LoaderCircle v-if="isMoved" class="size-4 animate-spin" />
                                        Lanjutkan
                                    </Link>
                            </div>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>

        </div>
    </AppLayout>
</template>
