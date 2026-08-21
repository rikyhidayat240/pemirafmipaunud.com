<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Kegiatan, type Kandidat } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Progress } from '@/components/ui/progress'

// Define props
const props = defineProps<{
    kegiatan: Kegiatan & { kandidat: Kandidat[] };
    votesByAngkatan?: Array<{
        angkatan: number;
        total_mahasiswa: number;
        jumlah_pemilih: number;
        persentase: number;
    }>;
    votesByProdi?: Array<{
        id_program_studi: number;
        nama_prodi: string;
        total_mahasiswa: number;
        jumlah_pemilih: number;
        persentase: number;
    }>;
}>();

const title = `Hasil Pemilihan ${props.kegiatan.nama}`;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: `Hasil Pemilihan ${props.kegiatan.nama}`,
        href: `/events/${props.kegiatan.id}`,
    },
];

// Get kandidat data
const kandidatList = computed(() => props.kegiatan.kandidat);

// Total suara
const totalSuara = computed(() => 
    kandidatList.value.reduce((sum, k) => sum + k.jumlah_suara, 0)
);

// Check if fakultas level
const isFakultasLevel = computed(() => props.kegiatan.ruang_lingkup === 'fakultas');

// Check if 2 kandidat (for layout purposes)
const isTwoKandidat = computed(() => kandidatList.value.length === 2);

// Height untuk bar (max 250px)
const getBarHeight = (jumlahSuara: number) => {
    const maxHeight = 250;
    if (totalSuara.value === 0) return 0;
    return (jumlahSuara / totalSuara.value) * maxHeight;
};

// Get bar color based on index
const getBarColor = (index: number) => {
    const colors = ['#5465D1', '#BD4C4C', '#4CAF50', '#FF9800', '#9C27B0', '#00BCD4'];
    return colors[index % colors.length];
};

const handleProgramStudi = (id_program_studi: number) => {
    const programStudiMap: Record<number, string> = {
        1: 'Kimia',
        2: 'Fisika',
        3: 'Biologi',
        4: 'Matematika',
        5: 'Farmasi',
        6: 'Informatika',
    };
    return programStudiMap[id_program_studi] || 'Unknown';
}

// Determine frame image based on ruang lingkup
const frameImage = computed(() => {
    return isFakultasLevel.value ? '/images/frame-bem.webp' : '/images/frame-hima.webp';
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-2 overflow-x-auto">
            <!-- Hero Section -->
            <div class="relative flex flex-col items-center justify-center">
                <div class="w-full flex justify-between items-start relative">
                    <img src="/images/corner-image.png" alt="" class="w-12 sm:w-20 md:w-32 lg:w-40">
                    <img :src="`/images/${kegiatan.foto.replace('jpg', 'png')}`" alt="" class="h-12 sm:h-20 md:h-40 lg:h-50 my-auto">
                    <img src="/images/corner-image.png" alt="" class="w-12 sm:w-20 md:w-32 lg:w-40 transform -scale-x-100">
                    <h1
                        class="text-sm sm:text-xl md:text-2xl lg:text-4xl leading-4 sm:leading-6 md:leading-8 lg:leading-10 font-bold text-center text-primary uppercase absolute left-1/2 -translate-x-1/2 top-1/2 -translate-y-1/2 px-2
                            drop-shadow-[0_2px_4px_rgba(255,255,255,0.8)] dark:drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                        {{ kegiatan.nama }}
                    </h1>
                </div>
            </div>

            <!-- Kandidat Section -->
            <div class="mx-auto w-full max-w-7xl px-2 sm:px-4 py-4 sm:py-8">
                <!-- Layout untuk 2 Kandidat (Berhadapan) - Stack on mobile -->
                <div v-if="isTwoKandidat">
                    <!-- Mobile Layout (Stacked vertically) -->
                    <div class="md:hidden space-y-6 px-4">
                        <!-- Kandidat 1 -->
                        <div class="flex items-center gap-4">
                            <!-- Frame & Image -->
                            <div class="relative w-40 shrink-0">
                                <img :src="frameImage" class="w-full" />
                                
                                <!-- BEM Style -->
                                <div v-if="isFakultasLevel">
                                    <img :src="kandidatList[0].foto ? `/storage/${kandidatList[0].foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidatList[0].no_urut}`"
                                        class="absolute bottom-1 right-1 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidatList[0].foto ? `max-h-28` : `max-h-20 right-4`" />
                                    <h1 class="absolute z-20 top-6 font-poppins font-black text-white text-[0.5rem] -rotate-90"
                                        style="text-shadow: 0px 6px 0px rgba(255,255,255,0.3), 0px 12px 0px rgba(255,255,255,0.1);">
                                        PASLON BEM
                                    </h1>
                                    <h1 class="absolute z-20 bottom-[0.4rem] left-[0.4rem] md:bottom-3 md:left-3 font-poppins font-black text-white text-xs md:text-base">
                                        {{ kandidatList[0].no_urut }}
                                    </h1>
                                </div>

                                <!-- HIMA Style -->
                                <div v-else>
                                    <img :src="kandidatList[0].foto ? `/storage/${kandidatList[0].foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidatList[0].no_urut}`"
                                        class="absolute -right-1 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidatList[0].foto ? `max-h-32` : `max-h-24 right-4`" />
                                    <h1 class="absolute z-20 top-8 font-poppins font-black text-white text-[0.5rem] -rotate-90"
                                        style="text-shadow: 0px 6px 0px rgba(255,255,255,0.3), 0px 12px 0px rgba(255,255,255,0.1);">
                                        CAKAHIMA
                                    </h1>
                                    <h1 class="absolute z-20 bottom-3 left-3 font-poppins font-black text-white text-sm">
                                        {{ kandidatList[0].no_urut }}
                                    </h1>
                                </div>
                            </div>

                            <!-- Info & Bar -->
                            <div class="flex-1 flex flex-col gap-2">
                                <div v-if="!kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')"
                                    class="text-left">
                                    <p class="text-xs font-semibold">
                                        {{ kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                        <span class="text-[0.65rem] text-muted-foreground">
                                            ({{ handleProgramStudi(kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.id_program_studi!) }}'
                                            {{ kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.angkatan.toString().slice(-2) }})
                                        </span>
                                    </p>
                                    <p v-if="isFakultasLevel && kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'wakil')" 
                                        class="text-xs font-semibold">
                                        {{ kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                        <span class="text-[0.65rem] text-muted-foreground">
                                            ({{ handleProgramStudi(kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.id_program_studi!) }}'
                                            {{ kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.angkatan.toString().slice(-2) }})
                                        </span>
                                    </p>
                                </div>
                                <p v-else class="font-semibold text-sm">Kotak Kosong</p>

                                <!-- Bar Horizontal -->
                                <div class="flex items-center gap-2">
                                    <div class="w-full h-6 rounded-lg transition-all duration-500"
                                        :style="{ 
                                            width: `${totalSuara > 0 ? (kandidatList[0].jumlah_suara / totalSuara) * 100 : 0}%`,
                                            backgroundColor: getBarColor(0)
                                        }">
                                    </div>
                                    <span class="text-sm font-bold min-w-8" :style="{ color: getBarColor(0) }">
                                        {{ kandidatList[0].jumlah_suara }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Kandidat 2 -->
                        <div class="flex items-center gap-4">
                            <!-- Frame & Image -->
                            <div class="relative w-40 shrink-0">
                                <img :src="frameImage" class="w-full" />
                                
                                <!-- BEM Style -->
                                <div v-if="isFakultasLevel">
                                    <img :src="kandidatList[1].foto ? `/storage/${kandidatList[1].foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidatList[1].no_urut}`"
                                        class="absolute bottom-1 right-1 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidatList[1].foto ? `max-h-28` : `max-h-20 right-4`" />
                                    <h1 class="absolute z-20 top-6 font-poppins font-black text-white text-[0.5rem] -rotate-90"
                                        style="text-shadow: 0px 6px 0px rgba(255,255,255,0.3), 0px 12px 0px rgba(255,255,255,0.1);">
                                        PASLON BEM
                                    </h1>
                                    <h1 class="absolute z-20 bottom-[0.4rem] left-[0.4rem] md:bottom-3 md:left-3 font-poppins font-black text-white text-xs md:text-base">
                                        {{ kandidatList[1].no_urut }}
                                    </h1>
                                </div>

                                <!-- HIMA Style -->
                                <div v-else>
                                    <img :src="kandidatList[1].foto ? `/storage/${kandidatList[1].foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidatList[1].no_urut}`"
                                        class="absolute -right-1 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidatList[1].foto ? `max-h-32` : `max-h-24 right-4`" />
                                    <h1 class="absolute z-20 top-8 font-poppins font-black text-white text-[0.5rem] -rotate-90"
                                        style="text-shadow: 0px 6px 0px rgba(255,255,255,0.3), 0px 12px 0px rgba(255,255,255,0.1);">
                                        CAKAHIMA
                                    </h1>
                                    <h1 class="absolute z-20 bottom-3 left-3 font-poppins font-black text-white text-sm">
                                        {{ kandidatList[1].no_urut }}
                                    </h1>
                                </div>
                            </div>

                            <!-- Info & Bar -->
                            <div class="flex-1 flex flex-col gap-2">
                                <div v-if="!kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')"
                                    class="text-left">
                                    <p class="text-xs font-semibold">
                                        {{ kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                        <span class="text-[0.65rem] text-muted-foreground">
                                            ({{ handleProgramStudi(kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.id_program_studi!) }}'
                                            {{ kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.angkatan.toString().slice(-2) }})
                                        </span>
                                    </p>
                                    <p v-if="isFakultasLevel && kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'wakil')" 
                                        class="text-xs font-semibold">
                                        {{ kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                        <span class="text-[0.65rem] text-muted-foreground">
                                            ({{ handleProgramStudi(kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.id_program_studi!) }}'
                                            {{ kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.angkatan.toString().slice(-2) }})
                                        </span>
                                    </p>
                                </div>
                                <p v-else class="font-semibold text-sm">Kotak Kosong</p>

                                <!-- Bar Horizontal -->
                                <div class="flex items-center gap-2">
                                    <div class="w-full h-6 rounded-lg transition-all duration-500"
                                        :style="{ 
                                            width: `${totalSuara > 0 ? (kandidatList[1].jumlah_suara / totalSuara) * 100 : 0}%`,
                                            backgroundColor: getBarColor(1)
                                        }">
                                    </div>
                                    <span class="text-sm font-bold min-w-8" :style="{ color: getBarColor(1) }">
                                        {{ kandidatList[1].jumlah_suara }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop/Tablet Layout (Original) -->
                    <div class="hidden md:grid grid-cols-5 items-end gap-2 lg:gap-4">
                        <!-- Kandidat 1 -->
                        <div class="col-span-2 flex flex-col items-center justify-center gap-2 lg:gap-4">
                            <!-- Frame & Image Kandidat 1 -->
                            <div class="relative w-full max-w-40 sm:max-w-xs lg:max-w-md">
                                <img :src="frameImage" class="w-full max-h-84" />
                                
                                <!-- BEM Style -->
                                <div v-if="isFakultasLevel">
                                    <img :src="kandidatList[0].foto ? `/storage/${kandidatList[0].foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidatList[0].no_urut}`"
                                        class="absolute bottom-1 sm:bottom-2 right-1 sm:right-2 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidatList[0].foto ? `max-h-36 sm:max-h-56 lg:max-h-72` : `max-h-24 sm:max-h-40 right-4 sm:right-8`" />
                                    <h1 class="absolute z-20 top-6 sm:top-12 lg:top-16 font-poppins font-black text-white text-[0.6rem] sm:text-sm lg:text-base -rotate-90"
                                        style="text-shadow: 0px 12px 0px rgba(255,255,255,0.3), 0px 24px 0px rgba(255,255,255,0.1);">
                                        PASLON BEM
                                    </h1>
                                    <h1 class="absolute z-20 bottom-2 left-2 sm:bottom-4 sm:left-4 lg:bottom-[1.3rem] lg:left-[1.3rem] font-poppins font-black text-white text-lg sm:text-xl lg:text-2xl">
                                        {{ kandidatList[0].no_urut }}
                                    </h1>
                                </div>

                                <!-- HIMA Style -->
                                <div v-else>
                                    <img :src="kandidatList[0].foto ? `/storage/${kandidatList[0].foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidatList[0].no_urut}`"
                                        class="absolute -right-1 sm:-right-2 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidatList[0].foto ? `max-h-40 sm:max-h-64 lg:max-h-80` : `max-h-24 sm:max-h-40 right-4 sm:right-8`" />
                                    <h1 class="absolute z-20 top-8 sm:top-16 lg:top-20 font-poppins font-black text-white text-xs sm:text-base lg:text-lg -rotate-90"
                                        style="text-shadow: 0px 12px 0px rgba(255,255,255,0.3), 0px 24px 0px rgba(255,255,255,0.1);">
                                        CAKAHIMA
                                    </h1>
                                    <h1 class="absolute z-20 bottom-4 left-4 sm:bottom-7 sm:left-7 lg:bottom-9 lg:left-10 font-poppins font-black text-white text-lg sm:text-xl lg:text-2xl">
                                        {{ kandidatList[0].no_urut }}
                                    </h1>
                                </div>
                            </div>

                            <!-- Info Kandidat 1 -->
                            <div v-if="!kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')"
                                class="text-center">
                                <p :class="!isFakultasLevel && 'flex flex-col gap-1'" class="text-xs sm:text-sm lg:text-base font-semibold">
                                    {{ kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                    <span class="text-[0.65rem] sm:text-sm text-muted-foreground">
                                        ({{ handleProgramStudi(kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.id_program_studi!) }}'
                                        {{ kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.angkatan.toString().slice(-2) }})
                                    </span>
                                </p>
                                <p v-if="isFakultasLevel && kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'wakil')" 
                                    class="text-xs sm:text-sm lg:text-base font-semibold">
                                    {{ kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                    <span class="text-[0.65rem] sm:text-sm text-muted-foreground">
                                        ({{ handleProgramStudi(kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.id_program_studi!) }}'
                                        {{ kandidatList[0].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.angkatan.toString().slice(-2) }})
                                    </span>
                                </p>
                            </div>
                            <p v-else class="font-semibold text-base sm:text-xl lg:text-2xl">Kotak Kosong</p>
                        </div>

                        <!-- Bar Suara (Berhadapan) -->
                        <div class="col-span-1 flex items-end justify-center gap-4 sm:gap-8 pb-2 sm:pb-4">
                            <!-- Bar Kandidat 1 -->
                            <div class="flex flex-col items-center">
                                <div
                                    class="flex h-full w-8 sm:w-12 lg:w-16 items-end justify-center rounded-t-lg font-bold text-white transition-all duration-500"
                                    :style="{ 
                                        height: `${getBarHeight(kandidatList[0].jumlah_suara)}px`,
                                        backgroundColor: getBarColor(0)
                                    }"
                                >
                                </div>
                                <div class="mt-1 sm:mt-2 text-sm sm:text-base lg:text-lg font-bold" :style="{ color: getBarColor(0) }">
                                    {{ kandidatList[0].jumlah_suara }}
                                </div>
                            </div>

                            <!-- Bar Kandidat 2 -->
                            <div class="flex flex-col items-center">
                                <div
                                    class="flex w-8 sm:w-12 lg:w-16 items-end justify-center rounded-t-lg font-bold text-white transition-all duration-500"
                                    :style="{ 
                                        height: `${getBarHeight(kandidatList[1].jumlah_suara)}px`,
                                        backgroundColor: getBarColor(1)
                                    }"
                                >
                                </div>
                                <div class="mt-1 sm:mt-2 text-sm sm:text-base lg:text-lg font-bold" :style="{ color: getBarColor(1) }">
                                    {{ kandidatList[1].jumlah_suara }}
                                </div>
                            </div>
                        </div>

                        <!-- Kandidat 2 -->
                        <div class="col-span-2 flex flex-col items-center justify-center gap-2 lg:gap-4">
                            <!-- Frame & Image Kandidat 2 -->
                            <div class="relative w-full max-w-40 sm:max-w-xs lg:max-w-md">
                                <img :src="frameImage" class="w-full max-h-84" />
                                
                                <!-- BEM Style -->
                                <div v-if="isFakultasLevel">
                                    <img :src="kandidatList[1].foto ? `/storage/${kandidatList[1].foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidatList[1].no_urut}`"
                                        class="absolute bottom-1 sm:bottom-2 right-1 sm:right-2 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidatList[1].foto ? `max-h-36 sm:max-h-56 lg:max-h-72` : `max-h-24 sm:max-h-40 right-4 sm:right-8`" />
                                    <h1 class="absolute z-20 top-6 sm:top-12 lg:top-16 font-poppins font-black text-white text-[0.6rem] sm:text-sm lg:text-base -rotate-90"
                                        style="text-shadow: 0px 12px 0px rgba(255,255,255,0.3), 0px 24px 0px rgba(255,255,255,0.1);">
                                        PASLON BEM
                                    </h1>
                                    <h1 class="absolute z-20 bottom-2 left-2 sm:bottom-4 sm:left-4 lg:bottom-[1.3rem] lg:left-[1.3rem] font-poppins font-black text-white text-lg sm:text-xl lg:text-2xl">
                                        {{ kandidatList[1].no_urut }}
                                    </h1>
                                </div>

                                <!-- HIMA Style -->
                                <div v-else>
                                    <img :src="kandidatList[1].foto ? `/storage/${kandidatList[1].foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidatList[1].no_urut}`"
                                        class="absolute -right-1 sm:-right-2 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidatList[1].foto ? `max-h-40 sm:max-h-64 lg:max-h-80` : `max-h-24 sm:max-h-40 right-4 sm:right-8`" />
                                    <h1 class="absolute z-20 top-8 sm:top-16 lg:top-20 font-poppins font-black text-white text-xs sm:text-base lg:text-lg -rotate-90"
                                        style="text-shadow: 0px 12px 0px rgba(255,255,255,0.3), 0px 24px 0px rgba(255,255,255,0.1);">
                                        CAKAHIMA
                                    </h1>
                                    <h1 class="absolute z-20 bottom-4 left-4 sm:bottom-7 sm:left-7 lg:bottom-9 lg:left-10 font-poppins font-black text-white text-lg sm:text-xl lg:text-2xl">
                                        {{ kandidatList[1].no_urut }}
                                    </h1>
                                </div>
                            </div>

                            <!-- Info Kandidat 2 -->
                            <div v-if="!kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')"
                                class="text-center">
                                <p :class="!isFakultasLevel && 'flex flex-col gap-1'" class="text-xs sm:text-sm lg:text-base font-semibold">
                                    {{ kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                    <span class="text-[0.65rem] sm:text-sm text-muted-foreground">
                                        ({{ handleProgramStudi(kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.id_program_studi!) }}'
                                        {{ kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.angkatan.toString().slice(-2) }})
                                    </span>
                                </p>
                                <p v-if="isFakultasLevel && kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'wakil')" 
                                    class="text-xs sm:text-sm lg:text-base font-semibold">
                                    {{ kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                    <span class="text-[0.65rem] sm:text-sm text-muted-foreground">
                                        ({{ handleProgramStudi(kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.id_program_studi!) }}'
                                        {{ kandidatList[1].mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.angkatan.toString().slice(-2) }})
                                    </span>
                                </p>
                            </div>
                            <p v-else class="font-semibold text-base sm:text-xl lg:text-2xl">Kotak Kosong</p>
                        </div>
                    </div>
                </div>

                <!-- Layout untuk > 2 Kandidat -->
                <div v-else>
                    <!-- Mobile Layout (Stacked vertically) -->
                    <div class="md:hidden space-y-6 px-4">
                        <div v-for="(kandidat, index) in kandidatList" :key="kandidat.id" class="flex items-center gap-4">
                            <!-- Frame & Image -->
                            <div class="relative w-40 shrink-0">
                                <img :src="frameImage" class="w-full" />
                                
                                <!-- BEM Style -->
                                <div v-if="isFakultasLevel">
                                    <img :src="kandidat.foto ? `/storage/${kandidat.foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidat.no_urut}`"
                                        class="absolute -right-1 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidat.foto ? `max-h-28` : `max-h-20 right-4`" />
                                    <h1 class="absolute z-20 top-8 font-poppins font-black text-white text-[0.5rem] -rotate-90"
                                        style="text-shadow: 0px 6px 0px rgba(255,255,255,0.3), 0px 12px 0px rgba(255,255,255,0.1);">
                                        PASLON BEM
                                    </h1>
                                    <h1 class="absolute z-20 bottom-3 left-3 font-poppins font-black text-white text-sm md:text-base">
                                        {{ kandidat.no_urut }}
                                    </h1>
                                </div>

                                <!-- HIMA Style -->
                                <div v-else>
                                    <img :src="kandidat.foto ? `/storage/${kandidat.foto}` : `/images/kotak-kosong.webp`"
                                        :alt="`Kandidat ${kandidat.no_urut}`"
                                        class="absolute -right-1 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                        :class="kandidat.foto ? `max-h-32` : `max-h-20 right-4`" />
                                    <h1 class="absolute z-20 top-8 font-poppins font-black text-white text-[0.5rem] -rotate-90"
                                        style="text-shadow: 0px 6px 0px rgba(255,255,255,0.3), 0px 12px 0px rgba(255,255,255,0.1);">
                                        CAKAHIMA
                                    </h1>
                                    <h1 class="absolute z-20 bottom-3 left-3 font-poppins font-black text-white text-sm md:text-base">
                                        {{ kandidat.no_urut }}
                                    </h1>
                                </div>
                            </div>

                            <!-- Info & Bar -->
                            <div class="flex-1 flex flex-col gap-2">
                                <div v-if="!kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')"
                                    class="text-left">
                                    <p class="text-xs font-semibold">
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                        <span class="text-[0.65rem] text-muted-foreground">
                                            ({{ handleProgramStudi(kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.id_program_studi!) }}'
                                            {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.angkatan.toString().slice(-2) }})
                                        </span>
                                    </p>
                                    <p v-if="isFakultasLevel && kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')" 
                                        class="text-xs font-semibold">
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                        <span class="text-[0.65rem] text-muted-foreground">
                                            ({{ handleProgramStudi(kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.id_program_studi!) }}'
                                            {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.angkatan.toString().slice(-2) }})
                                        </span>
                                    </p>
                                </div>
                                <p v-else class="font-semibold text-sm">Kotak Kosong</p>

                                <!-- Bar Horizontal -->
                                <div class="flex items-center gap-2">
                                    <div class="w-full h-6 rounded-lg transition-all duration-500"
                                        :style="{ 
                                            width: `${totalSuara > 0 ? (kandidat.jumlah_suara / totalSuara) * 100 : 0}%`,
                                            backgroundColor: getBarColor(index)
                                        }">
                                    </div>
                                    <span class="text-sm font-bold min-w-8" :style="{ color: getBarColor(index) }">
                                        {{ kandidat.jumlah_suara }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop/Tablet Layout -->
                    <div class="hidden md:grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 justify-items-center">
                        <div v-for="(kandidat, index) in kandidatList" :key="kandidat.id"
                            class="w-full max-w-xs flex flex-col items-center justify-center gap-2 sm:gap-4">
                            <div class="relative w-full flex gap-4 items-end">
                                <!-- Frame & Image Container -->
                                <div class="relative w-full mb-4">
                                    <img :src="frameImage" class="w-full" />

                                    <!-- BEM Style -->
                                    <div v-if="isFakultasLevel">
                                        <img :src="kandidat.foto ? `/storage/${kandidat.foto}` : `/images/kotak-kosong.webp`"
                                            :alt="`Kandidat ${kandidat.no_urut}`"
                                            class="absolute -right-1 sm:-right-2 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                            :class="kandidat.foto ? `max-h-32 sm:max-h-48` : `max-h-24 sm:max-h-40 right-4 sm:right-8`" />
                                        <h1 class="absolute z-20 top-6 sm:top-12 lg:top-16 font-poppins font-black text-white text-[0.6rem] sm:text-sm lg:text-base -rotate-90"
                                            style="text-shadow: 0px 12px 0px rgba(255,255,255,0.3), 0px 24px 0px rgba(255,255,255,0.1);">
                                            PASLON BEM
                                        </h1>
                                        <h1 class="absolute z-20 bottom-2 left-2 sm:bottom-4 sm:left-4 lg:bottom-[1.3rem] lg:left-[1.3rem] font-poppins font-black text-white text-lg sm:text-xl lg:text-2xl">
                                            {{ kandidat.no_urut }}
                                        </h1>
                                    </div>

                                    <!-- HIMA Style -->
                                    <div v-else>
                                        <img :src="kandidat.foto ? `/storage/${kandidat.foto}` : `/images/kotak-kosong.webp`"
                                            :alt="`Kandidat ${kandidat.no_urut}`"
                                            class="absolute -right-1 sm:-right-2 w-auto place-self-end border-none mask-linear-180 mask-linear-from-40% mask-linear-to-85%"
                                            :class="kandidat.foto ? `max-h-40 sm:max-h-56` : `max-h-24 sm:max-h-40 right-4 sm:right-8`" />
                                        <h1 class="absolute z-20 top-10 sm:top-12 lg:top-14 font-poppins font-black text-white text-xs sm:text-base -rotate-90"
                                            style="text-shadow: 0px 12px 0px rgba(255,255,255,0.3), 0px 24px 0px rgba(255,255,255,0.1);">
                                            CAKAHIMA
                                        </h1>
                                        <h1 class="absolute z-20 bottom-4 left-4 sm:bottom-6 sm:left-6 lg:bottom-5 lg:left-5 font-poppins font-black text-white text-base sm:text-lg">
                                            {{ kandidat.no_urut }}
                                        </h1>
                                    </div>
                                </div>

                                <!-- Bar Suara (Below Frame) -->
                                <div class="flex flex-col items-center mt-2 sm:mt-4">
                                    <div
                                        class="flex w-8 sm:w-10 lg:w-12 items-end justify-center rounded-t-lg font-bold text-white transition-all duration-500"
                                        :style="{ 
                                            height: `${getBarHeight(kandidat.jumlah_suara)}px`,
                                            backgroundColor: getBarColor(index)
                                        }"
                                    >
                                    </div>
                                    <div class="mt-1 sm:mt-2 text-sm sm:text-base lg:text-lg font-bold" :style="{ color: getBarColor(index) }">
                                        {{ kandidat.jumlah_suara }}
                                    </div>
                                </div>
                            </div>

                            <!-- Nama Kandidat -->
                            <div v-if="!kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')"
                                class="text-center">
                                <p :class="!isFakultasLevel && 'flex flex-col gap-1'" class="text-xs sm:text-sm lg:text-base font-semibold">
                                    <span class="block">
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                    </span>
                                    <span class="text-[0.65rem] sm:text-sm text-muted-foreground">
                                        ({{ handleProgramStudi(kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.id_program_studi!) }}'
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.angkatan.toString().slice(-2) }})
                                    </span>
                                </p>
                                <p v-if="isFakultasLevel && kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')" 
                                    class="text-xs sm:text-sm lg:text-base font-semibold">
                                    {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                    <span class="text-[0.65rem] sm:text-sm text-muted-foreground">
                                        ({{ handleProgramStudi(kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.id_program_studi!) }}'
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.angkatan.toString().slice(-2) }})
                                    </span>
                                </p>
                            </div>

                            <p v-else class="font-semibold text-base sm:text-xl lg:text-2xl">Kotak Kosong</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Suara -->
            <div class="mb-2 sm:mb-4 text-center px-2" :class="!isTwoKandidat && !isFakultasLevel && 'mt-8 sm:mt-16'">
                <p class="text-sm sm:text-base lg:text-lg font-semibold text-primary">
                    Total Suara Masuk: {{ totalSuara }} / {{ kegiatan.total_mahasiswa }}
                </p>
            </div>

            <!-- Progress Bar Suara per Prodi (Fakultas) -->
            <div v-if="isFakultasLevel && votesByProdi" class="mx-auto w-full max-w-7xl px-4 pb-4 sm:pb-8 space-y-3 sm:space-y-4">
                <h2 class="text-base sm:text-lg lg:text-xl font-bold mb-2 sm:mb-4">Partisipasi per Program Studi</h2>
                <div v-for="vote in votesByProdi" :key="vote.id_program_studi" class="space-y-1 sm:space-y-2">
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1 sm:gap-0">
                        <h3 class="text-xs sm:text-sm lg:text-base font-medium">{{ vote.nama_prodi }}</h3>
                        <h3 class="text-xs sm:text-sm lg:text-base font-medium">
                            {{ vote.persentase }}% ({{ vote.jumlah_pemilih }}/{{ vote.total_mahasiswa }} suara)
                        </h3>
                    </div>
                    <Progress :model-value="Number(vote.persentase)" />
                </div>
            </div>

            <!-- Progress Bar Suara per Angkatan (Program Studi) -->
            <div v-if="!isFakultasLevel && votesByAngkatan" class="mx-auto w-full max-w-7xl px-4 pb-4 sm:pb-8 space-y-3 sm:space-y-4">
                <h2 class="text-base sm:text-lg lg:text-xl font-bold mb-2 sm:mb-4">Partisipasi per Angkatan</h2>
                <div v-for="vote in votesByAngkatan" :key="vote.angkatan" class="space-y-1 sm:space-y-2">
                    <div class="flex flex-col sm:flex-row sm:justify-between gap-1 sm:gap-0">
                        <h3 class="text-xs sm:text-sm lg:text-base font-medium">Angkatan {{ vote.angkatan }}</h3>
                        <h3 class="text-xs sm:text-sm lg:text-base font-medium">
                            {{ vote.persentase }}% ({{ vote.jumlah_pemilih }}/{{ vote.total_mahasiswa }} suara)
                        </h3>
                    </div>
                    <Progress :model-value="Number(vote.persentase)" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>