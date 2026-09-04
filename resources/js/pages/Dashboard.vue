<script setup lang="ts">
import dayjs from 'dayjs';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, Kegiatan } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"

// define props
const props = defineProps<{
    kegiatan: Kegiatan[];
    waktu: Date | string;
}>();

// Link handler
const ctaLink = computed(() => {
    return auth.value.user ? '/terms' : '/login';
});

const candidateLink = (nama: string) => {
    const formattedName = nama.toLowerCase().replace(/\s+/g, '-');
    return `/candidates/${formattedName}`;
};

// Add computed property to filter kegiatan
const filteredKegiatan = computed(() => {
    if (!auth.value.user) return [];

    return props.kegiatan.filter(item => {
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

const timeRemaining = computed(() => {
    const target = new Date(props.waktu);
    const now = currentTime.value;
    const diff = target.getTime() - now.getTime();

    if (diff <= 0) {
        return { days: 0, hours: 0, minutes: 0, seconds: 0, expired: true };
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    return { days, hours, minutes, seconds, expired: false };
});

const getTimeUntilStart = (startTime: Date, endTime: Date) => {
    const target = new Date(startTime);
    const now = currentTime.value;
    const diff = target.getTime() - now.getTime();

    if (diff <= 0) {
        const endTarget = new Date(endTime);
        if (now.getTime() > endTarget.getTime()) {
            return { text: "Telah selesai", expired: true };
        }
        return { text: "Sedang berlangsung", expired: true };
    }

    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    let text = "";
    if (days > 0) {
        text = `${days} hari ${hours} jam`;
    } else if (hours > 0) {
        text = `${hours} jam ${minutes} menit`;
    } else {
        text = `${minutes} menit`;
    }

    return { text, expired: false };
};

const formatTime = (time: number) => {
    return time.toString().padStart(2, '0');
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

// Accordion FAQ data
const defaultValue = "item-1"
const accordionItems = [
    { value: "item-1", title: "Apa saja syarat untuk mengikuti pemilihan?", content: "Anda harus terdaftar sebagai mahasiswa aktif di fakultas dan program studi yang sesuai. Jika program studi Anda tidak termasuk program sarjana, maka Anda tidak dapat melakukan pemilihan." },
    { value: "item-2", title: "Bagaimana cara melakukan registrasi akun?", content: "Anda cukup menyiapkan NIM dan nama lengkap yang sesuai dengan profil pada laman IMISSU. Kemudian, ikuti langkah-langkah yang terdapat pada laman registrasi akun." },
    { value: "item-3", title: "Bagaimana cara login ke dalam laman pemilihan?", content: "Anda dapat melakukan login dengan menggunakan email dan kata sandi yang telah Anda daftarkan sebelumnya. Pastikan email yang terdaftar merupakan email aktif dari universitas." },
    { value: "item-4", title: "Bagaimana jika saya lupa kata sandi?", content: "Anda dapat melakukan reset kata sandi melalui laman login dengan mengklik tautan 'Lupa kata sandi?'. Ikuti langkah-langkah yang diberikan untuk mengatur ulang kata sandi Anda." },
    { value: "item-5", title: "Kegiatan apa saja yang dapat saya ikuti?", content: "Anda dapat mengikuti kegiatan pemilihan umum untuk memilih pasangan calon ketua dan wakil ketua BEM FMIPA serta memilih calon ketua himpunan di masing-masing program studi." },
    { value: "item-6", title: "Bagaimana tata cara melakukan pemilihan?", content: "Anda diharuskan login terlebih dahulu untuk dapat melakukan pemilihan. Kemudian, Anda dapat melihat informasi kandidat setiap kegiatan sebelum melakukan pemilihan. Setelah memulai proses pemilihan, Anda harus menyelesaikan semua kegiatan pemilihan sebelum Anda dapat keluar dari situs pemilihan." },
    { value: "item-7", title: "Apakah saya dapat mengubah pilihan saya setelah memilih?", content: "Tidak, setelah Anda mengklik tombol 'Selesai' pada halaman pemilihan, pilihan Anda akan terkunci dan tidak dapat diubah. Anda juga hanya memiliki kesempatan sekali saja untuk melakukan pemilihan." },
    { value: "item-8", title: "Bagaimana cara memperbarui profil dan akun?", content: "Anda dapat memperbarui profil dan akun Anda melalui halaman pengaturan akun. Anda juga dapat mengubah kata sandi Anda di halaman ini. Pastikan untuk menyimpan segala perubahan yang telah Anda buat." },
]

// Page title and breadcrumbs
const page = usePage();
const auth = computed(() => page.props.auth);
const title = auth.value.user ? 'Beranda' : 'Selamat Datang';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Beranda',
        href: '/dashboard',
    },
];
</script>

<template>

    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-0 overflow-hidden">

            <!-- ===== HERO SECTION ===== -->
            <div class="relative min-h-[88vh] flex flex-col items-center justify-center overflow-hidden px-4">

                <!-- Constellation Backgrounds for Hero Section -->
                <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden flex justify-center">
                    <div class="relative w-full h-full max-w-[1920px]">
                        <!-- Large Constellation (Group 186) -->
                        <img src="/group-186.png" class="absolute top-[5%] left-[2%] w-[25vw] max-w-[300px] min-w-[150px] opacity-80 animate-pulse" style="animation-duration: 6s;" alt="">
                        
                        <!-- Kite constellation (Group 220) -->
                        <img src="/group-220.png" class="absolute top-[5%] right-[5%] w-[18vw] max-w-[200px] min-w-[100px] opacity-80 animate-pulse" style="animation-duration: 8s;" alt="">
                        
                        <!-- Huge 4-Pointed Star (Group 227) -->
                        <img src="/group-227.png" class="absolute bottom-[5%] right-[2%] w-[20vw] max-w-[250px] min-w-[120px] opacity-70 animate-pulse" style="animation-duration: 6s;" alt="">
                    </div>
                </div>

                <!-- Center content -->
                <div class="relative z-10 flex flex-col items-center justify-center gap-6 text-center max-w-2xl">
                    <!-- Logo glowing -->
                    <div class="relative">
                        <div class="absolute inset-0 rounded-full" style="background: radial-gradient(circle, rgba(201,162,39,0.3) 0%, transparent 70%); transform: scale(2.5); animation: shimmer-gold 3s ease-in-out infinite;"></div>
                        <img src="/Logo pemira.png" alt="Logo PEMIRA"
                            class="relative size-24 sm:size-28 lg:size-32 drop-shadow-2xl object-contain"
                            onerror="this.style.display='none'" />
                    </div>

                    <!-- Decorative 4-pointed stars -->
                    <div class="star-4 absolute -top-2 left-[20%] size-4 bg-yellow-400/30 animate-pulse" style="animation-duration: 5s;"></div>
                    <div class="star-4 absolute top-8 right-[18%] size-3 bg-yellow-400/20 animate-pulse" style="animation-duration: 7s;"></div>

                    <!-- Heading -->
                    <div class="space-y-2">
                        <p class="text-xs sm:text-sm font-semibold uppercase tracking-[0.3em] text-white/50">Pemilihan Umum Raya</p>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black uppercase leading-tight poppins-font">
                            <span class="text-white">PEMIRA LM</span><br/>
                            <span class="text-gold-light" style="color: #f0c040;">FMIPA {{ dayjs().year() }}</span>
                        </h1>
                    </div>

                    <!-- Tagline -->
                    <div class="space-y-1">
                        <p class="text-sm sm:text-base text-white/60">
                            Saatnya suaramu menentukan arah kepemimpinan Lembaga Mahasiswa
                        </p>
                        <p class="text-sm sm:text-base text-white/50">
                            Fakultas Matematika dan Ilmu Pengetahuan Alam
                        </p>
                    </div>

                    <!-- Countdown (if not expired) -->
                    <div v-if="!timeRemaining.expired" class="flex items-center gap-4 sm:gap-6">
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-black text-yellow-400 poppins-font">{{ formatTime(timeRemaining.days) }}</div>
                            <div class="text-xs text-white/40 uppercase tracking-wider mt-1">Hari</div>
                        </div>
                        <div class="text-2xl text-yellow-400/40 font-bold">:</div>
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-black text-yellow-400 poppins-font">{{ formatTime(timeRemaining.hours) }}</div>
                            <div class="text-xs text-white/40 uppercase tracking-wider mt-1">Jam</div>
                        </div>
                        <div class="text-2xl text-yellow-400/40 font-bold">:</div>
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-black text-yellow-400 poppins-font">{{ formatTime(timeRemaining.minutes) }}</div>
                            <div class="text-xs text-white/40 uppercase tracking-wider mt-1">Menit</div>
                        </div>
                        <div class="text-2xl text-yellow-400/40 font-bold">:</div>
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-black text-yellow-400 poppins-font">{{ formatTime(timeRemaining.seconds) }}</div>
                            <div class="text-xs text-white/40 uppercase tracking-wider mt-1">Detik</div>
                        </div>
                    </div>

                    <!-- CTA Button -->
                    <Link :href="ctaLink">
                        <button class="btn-gold px-8 py-3 rounded-full text-sm font-bold tracking-wide shadow-lg" style="box-shadow: 0 0 30px rgba(201,162,39,0.4);">
                            Masuk ke Bilik Suara ➜
                        </button>
                    </Link>

                    <!-- Sub tagline -->
                    <p class="text-xs text-white/25 italic">
                        Satu suara, satu harapan — berikan yang terbaik untuk FMIPA
                    </p>
                </div>
            </div>

            <!-- ===== KEGIATAN SECTION ===== -->
            <div v-if="auth.user && filteredKegiatan.length > 0" class="px-4 py-12">
                <div class="max-w-7xl mx-auto">
                    <h2 class="text-xl md:text-2xl font-bold text-center text-white mb-8">
                        <span class="text-yellow-400">Kegiatan</span> Yang Anda Dapat Ikuti
                    </h2>
                    <div class="grid md:grid-cols-2 gap-4 max-w-3xl mx-auto">
                        <div v-for="item in filteredKegiatan" :key="item.id"
                            class="pemira-card rounded-xl overflow-hidden hover:border-yellow-400/40 transition-all duration-300">
                            <div class="relative">
                                <img :src="`/storage/${item.foto}`" alt="" class="w-full h-44 object-cover" />
                                <div class="absolute inset-0" style="background: linear-gradient(to top, rgba(14,18,55,0.8) 0%, transparent 60%);"></div>
                                <div class="absolute bottom-3 left-4 right-4">
                                    <p class="font-bold text-white text-sm">{{ item.nama }}</p>
                                    <p class="text-xs text-yellow-400/80 mt-0.5">
                                        {{ getTimeUntilStart(item.waktu_mulai as Date, item.waktu_selesai as Date).expired ?
                                            getTimeUntilStart(item.waktu_mulai as Date, item.waktu_selesai as Date).text :
                                            `Dimulai dalam ${getTimeUntilStart(item.waktu_mulai as Date, item.waktu_selesai as Date).text}`
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div class="p-4">
                                <Link :href="candidateLink(item.nama)">
                                    <button class="w-full py-2 rounded-lg text-sm font-semibold text-white/70 border border-white/10 hover:border-yellow-400/40 hover:text-yellow-400 transition-all">
                                        Lihat Kandidat
                                    </button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== FAQ SECTION ===== -->
            <div class="px-4 py-12 max-w-4xl w-full mx-auto">
                <h2 class="text-xl md:text-2xl font-bold text-center text-white mb-8">
                    Yang <span class="text-yellow-400">Sering</span> Ditanyakan
                </h2>
                <Accordion type="single" class="w-full" collapsible :default-value="defaultValue">
                    <AccordionItem v-for="item in accordionItems" :key="item.value" :value="item.value"
                        class="border-b border-white/10">
                        <AccordionTrigger class="text-white/80 hover:text-yellow-400 text-left py-4 text-sm font-medium transition-colors [&[data-state=open]]:text-yellow-400">
                            {{ item.title }}
                        </AccordionTrigger>
                        <AccordionContent class="text-white/50 text-sm pb-4 leading-relaxed">
                            {{ item.content }}
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </div>

        </div>
    </AppLayout>
</template>
