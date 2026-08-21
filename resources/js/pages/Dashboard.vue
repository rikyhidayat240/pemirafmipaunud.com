<script setup lang="ts">
import dayjs from 'dayjs';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/components/ui/button/Button.vue';
import { type BreadcrumbItem, Kegiatan } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import { Card, CardHeader, CardContent, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import Autoplay from "embla-carousel-autoplay"
import { Carousel, CarouselContent, CarouselItem, } from "@/components/ui/carousel"

const plugin = Autoplay({
    delay: 5000,
    stopOnMouseEnter: true,
    stopOnInteraction: false,
})

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

// Add a function to calculate time difference for individual activities
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



// Helper function to format time values
const formatTime = (time: number) => {
    return time.toString().padStart(2, '0');
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

const heroImages = [
    '/images/foto-slide-hero/IMG_2.webp',
    '/images/foto-slide-hero/IMG_1.webp',
    '/images/foto-slide-hero/IMG_3.webp',
]
</script>

<template>

    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 overflow-hidden">
            <!-- Countdown timer -->
            <div class="relative min-h-[90vh] flex flex-1 justify-center items-center">
                <!-- Content with relative positioning and higher z-index -->
                <Carousel class="absolute w-full saturate-0 md:saturate-0 md:backdrop-blur" :plugins="[plugin]"
                    @mouseenter="plugin.stop" @mouseleave="[plugin.reset(), plugin.play()]">
                    <CarouselContent>
                        <CarouselItem v-for="image in heroImages" :key="image">
                            <div class="flex items-center justify-center">
                                <Card class="w-full">
                                    <CardContent class="flex items-center justify-center px-0">
                                        <img :src="image" alt="Placeholder" class="w-full h-[90vh] object-cover opacity-30 mask-y-from-80% mask-y-to-100%" />
                                        <!-- mask-linear-0 mask-linear-from-0% mask-linear-to-100% -->
                                    </CardContent>
                                </Card>
                            </div>
                        </CarouselItem>
                    </CarouselContent>
                </Carousel>

                <div class="relative z-10 px-4 space-y-4 md:space-y-6 flex flex-col items-center justify-center">
                    <!-- Header -->
                    <div class="stroke-black-500 text-center text-shadow-md text-shadow-foreground/30">
                        <h2 class="text-xl md:text-2xl lg:text-3xl font-bold text-foreground/50 mb-2">
                            PEMIRA FMIPA
                        </h2>
                        <p class="text-foreground/50 font-medium text-sm md:text-base max-w-lg">
                            Pemilihan Umum Raya Fakultas Matematika dan Ilmu Pengetahuan Alam {{ dayjs().year() }} akan dimulai dalam
                        </p>
                    </div>

                    <!-- Countdown Display -->
                    <div class="flex justify-center space-x-4 items-start text-shadow-md text-shadow-foreground/30">
                        <!-- Days -->
                        <div class="text-center">
                            <p class="text-4xl md:text-5xl lg:text-6xl font-bold text-foreground/50">
                                {{ formatTime(timeRemaining.days) }}
                            </p>
                            <p class="text-sm lg:text-base text-foreground/50 font-medium mt-2">
                                Hari
                            </p>
                        </div>

                        <div class="pt-2 text-xl md:text-2xl lg:text-3xl font-bold text-foreground/50">:</div>

                        <!-- Hours -->
                        <div class="text-center">
                            <p class="text-4xl md:text-5xl lg:text-6xl font-bold text-foreground/50">
                                {{ formatTime(timeRemaining.hours) }}
                            </p>
                            <p class="text-sm lg:text-base text-foreground/50 font-medium mt-2">
                                Jam
                            </p>
                        </div>

                        <div class="pt-2 text-xl md:text-2xl lg:text-3xl font-bold text-foreground/50">:</div>

                        <!-- Minutes -->
                        <div class="text-center">
                            <p class="text-4xl md:text-5xl lg:text-6xl font-bold text-foreground/50">
                                {{ formatTime(timeRemaining.minutes) }}
                            </p>
                            <p class="text-sm lg:text-base text-foreground/50 font-medium mt-2">
                                Menit
                            </p>
                        </div>

                        <div class="pt-2 text-xl md:text-2xl lg:text-3xl font-bold text-foreground/50">:</div>

                        <!-- Seconds -->
                        <div class="text-center">
                            <p class="text-4xl md:text-5xl lg:text-6xl font-bold text-foreground/50">
                                {{ formatTime(timeRemaining.seconds) }}
                            </p>
                            <p class="text-sm lg:text-base text-foreground/50 font-medium mt-2">
                                Detik
                            </p>
                        </div>
                    </div>

                    <!-- Status Message -->
                    <Link :href="ctaLink">
                    <Button variant="outline" size="lg"
                        class="text-base dark:bg-foreground dark:text-background dark:border-foreground dark:hover:bg-foreground/80">
                        Mulai Sekarang!
                    </Button>
                    </Link>
                </div>
            </div>

            <!-- List Kegiatan -->
            <div v-if="auth.user && filteredKegiatan.length > 0" class="px-4">
                <h1 class="text-lg md:text-xl lg:text-2xl mt-2 mb-6 font-bold text-center">Kegiatan Mendatang</h1>
                <div class="max-w-7xl mb-6 w-full grid place-self-center auto-rows-min gap-4 md:grid-cols-2">
                    <Card v-for="item in filteredKegiatan" :key="item.id" class="border shadow-sm shadow-foreground/10">
                        <CardHeader>
                            <img :src="`/storage/${item.foto}`" alt="" class="w-full h-64 object-cover rounded-md">
                        </CardHeader>
                        <CardContent class="space-y-2 px-6">
                            <CardTitle class="text-lg md:text-xl">{{ item.nama }}</CardTitle>
                            <CardDescription>
                                {{ getTimeUntilStart(item.waktu_mulai as Date, item.waktu_selesai as Date).expired ?
                                    getTimeUntilStart(item.waktu_mulai as Date, item.waktu_selesai as Date).text :
                                    `Dimulai dalam ${getTimeUntilStart(item.waktu_mulai as Date, item.waktu_selesai as Date).text}`
                                }}
                            </CardDescription>
                        </CardContent>
                        <CardFooter>
                            <Link :href="candidateLink(item.nama)">
                                <Button variant="default" size="default" class="w-full">
                                    Lihat Kandidat
                                </Button>
                            </Link>
                        </CardFooter>
                    </Card>
                </div>
            </div>

            <!-- FAQ Accordion -->
            <div class="w-full md:max-w-7xl place-self-center px-4">
                <h1 class="text-lg md:text-xl lg:text-2xl mt-2 mb-6 font-bold text-center">Yang Sering Ditanyakan</h1>
                <Accordion type="single" class="w-full" collapsible :default-value="defaultValue">
                    <AccordionItem v-for="item in accordionItems" :key="item.value" :value="item.value">
                        <AccordionTrigger>{{ item.title }}</AccordionTrigger>
                        <AccordionContent>
                            {{ item.content }}
                        </AccordionContent>
                    </AccordionItem>
                </Accordion>
            </div>
        </div>
    </AppLayout>
</template>
