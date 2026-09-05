<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, Kandidat, Kegiatan } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Page title and breadcrumbs
const page = usePage();
const auth = computed(() => page.props.auth);
const title = 'Kandidat Pemilihan';

// define props
const props = defineProps<{
    kegiatan: Kegiatan;
    kandidat: Kandidat[];
}>();

// Remove kandidat kotak kosong agar tidak tampil
const kandidat = computed(() => {
    return props.kandidat.filter(k => {
        const hasKotakKosong = k.mahasiswa.some(m => m.nama.toLowerCase().includes('kotak kosong'));
        return !hasKotakKosong;
    });
});

const formatMisi = (misi: string): string[] => {
    const misiItems = misi.split(/\d+\.\s/).filter(item => item.trim());
    return misiItems.map(item => item.trim());
}

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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Kembali',
        href: '/dashboard',
    },
    {
        title: `Kandidat ${props.kegiatan.nama}`,
        href: `/candidates/${props.kegiatan.nama.toLowerCase().replace(/\s+/g, '-')}`,
    },
];
</script>

<template>
    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-0 overflow-x-hidden">

            <!-- ===== PAGE HEADER ===== -->
            <div class="relative flex items-center justify-center py-8 px-4 overflow-hidden">
                <div class="absolute top-0 left-0 w-20 h-20 opacity-20"
                    style="background: linear-gradient(135deg, rgba(201,162,39,0.4) 0%, transparent 60%); clip-path: polygon(0 0, 100% 0, 0 100%);"></div>
                <div class="absolute top-0 right-0 w-20 h-20 opacity-20"
                    style="background: linear-gradient(225deg, rgba(201,162,39,0.4) 0%, transparent 60%); clip-path: polygon(0 0, 100% 0, 100% 100%);"></div>

                <div class="relative z-10 text-center">
                    <img :src="`/images/${kegiatan.foto?.replace('jpg', 'png') ?? 'background-logo-dpm.png'}`" alt=""
                        class="size-14 mx-auto drop-shadow-lg mb-3" onerror="this.style.display='none'" />
                    <p class="text-xs text-white/40 uppercase tracking-widest">Pemilihan Umum Raya · FMIPA</p>
                    <h1 class="text-xl sm:text-2xl font-black text-white uppercase poppins-font mt-1">
                        {{ kegiatan.nama }}
                    </h1>
                </div>
            </div>

            <!-- ===== KANDIDAT CARDS ===== -->
            <div class="max-w-3xl mx-auto w-full px-4 pb-12 space-y-6 tilt-3d-container">
                <h2 class="text-base font-bold text-center text-white/60 mb-6">
                    Kandidat <span class="text-yellow-400">{{ kegiatan.nama }}</span>
                </h2>

                <div v-for="(k, index) in kandidat" :key="k.id"
                    class="pemira-card tilt-3d-card rounded-2xl overflow-hidden hover:border-yellow-400/40 transition-all duration-500">

                    <div class="p-6">
                        <!-- Header row: photo + name + badges -->
                        <div class="flex items-start gap-4 mb-5">
                            <!-- Circular photo with number badge -->
                            <div class="relative flex-shrink-0 tilt-3d-child">
                                <div class="size-20 sm:size-24 rounded-full overflow-hidden border-2 border-yellow-400/20">
                                    <img :src="k.foto !== null ? `/storage/${k.foto}` : '/images/blank-profile-picture.webp'"
                                        alt="Foto Kandidat"
                                        class="w-full h-full object-cover" />
                                </div>
                                <!-- Number badge -->
                                <div class="absolute -top-1 -right-1 size-7 rounded-full flex items-center justify-center text-white font-black text-xs poppins-font shadow-lg"
                                    style="background: linear-gradient(135deg, #c9a227, #f0c040);">
                                    {{ k.no_urut }}
                                </div>
                            </div>

                            <!-- Name & tags -->
                            <div class="flex-1 min-w-0 pt-1 tilt-3d-child">
                                <h3 class="font-bold text-white text-base sm:text-lg leading-tight">
                                    <span v-for="(mhs, idx) in k.mahasiswa" :key="mhs.nim">
                                        {{ mhs.nama }}<span v-if="idx < k.mahasiswa.length - 1"> & </span>
                                    </span>
                                </h3>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <span v-for="(mhs, idx) in k.mahasiswa" :key="mhs.nim"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs border border-yellow-400/20 text-yellow-400/70"
                                        style="background: rgba(201,162,39,0.08);">
                                        {{ handleProgramStudi(mhs.id_program_studi) }}'{{ mhs.angkatan.toString().slice(-2) }} —
                                        Calon {{ mhs.pivot.jabatan.charAt(0).toUpperCase() + mhs.pivot.jabatan.slice(1) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="h-px w-full mb-5" style="background: linear-gradient(90deg, transparent, rgba(201,162,39,0.2), transparent);"></div>

                        <!-- Visi -->
                        <div class="mb-5">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="size-5 rounded-full border border-yellow-400/40 flex items-center justify-center flex-shrink-0">
                                    <div class="size-1.5 rounded-full bg-yellow-400"></div>
                                </div>
                                <h4 class="text-sm font-bold text-yellow-400">Visi</h4>
                            </div>
                            <p class="text-sm text-white/60 leading-relaxed pl-7">
                                {{ k.visi }}
                            </p>
                        </div>

                        <!-- Misi -->
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="size-5 rounded-full border border-yellow-400/40 flex items-center justify-center flex-shrink-0">
                                    <div class="size-1.5 rounded-full bg-yellow-400"></div>
                                </div>
                                <h4 class="text-sm font-bold text-yellow-400">Misi</h4>
                            </div>
                            <ol class="space-y-2 pl-7">
                                <li v-for="(misiItem, idx) in formatMisi(k.misi)" :key="idx"
                                    class="flex items-start gap-2.5 text-sm text-white/60 leading-relaxed">
                                    <span class="flex-shrink-0 size-5 rounded-full border border-yellow-400/20 text-yellow-400/70 flex items-center justify-center text-xs font-semibold">
                                        {{ idx + 1 }}
                                    </span>
                                    <span>{{ misiItem }}</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== CTA BUTTONS ===== -->
            <div class="max-w-sm mx-auto w-full px-4 pb-12 grid grid-cols-2 gap-4">
                <Link :href="route('dashboard')" class="w-full">
                    <button class="w-full py-3 rounded-full text-sm font-semibold text-white/50 border border-white/15 hover:border-yellow-400/30 hover:text-yellow-400 transition-all">
                        ← Ke Beranda
                    </button>
                </Link>
                <Link :href="route('terms')" class="w-full">
                    <button class="btn-gold w-full py-3 text-sm font-bold">
                        Ke Pemilihan ➜
                    </button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>