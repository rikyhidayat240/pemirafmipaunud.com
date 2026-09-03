<script setup lang="ts">
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import Vue3Signature from "vue3-signature";
import dayjs from 'dayjs';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, reactive, watch } from 'vue';
import { TriangleAlert, CheckCircle2, LoaderCircle, X, Undo } from 'lucide-vue-next';
import type { BreadcrumbItem, Kegiatan, Kandidat } from '@/types';
import { AlertDialog, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';

// Page title and breadcrumbs
const page = usePage();
const auth = computed(() => page.props.auth);
const title = 'Pemilihan';

// Define props
const props = defineProps<{
    kegiatanBem: Kegiatan & { kandidat: Kandidat[] } | null;
    kegiatanHima: Kegiatan & { kandidat: Kandidat[] } | null;
}>();

// Voting state
const currentStep = ref<'bem' | 'hima' | 'complete'>(props.kegiatanBem ? 'bem' : (props.kegiatanHima ? 'hima' : 'complete'));
const selectedBem = ref<number | null>(null);
const selectedHima = ref<number | null>(null);
const isVoting = ref(true);
const isMoved = ref(false);
const isSubmitting = ref(false);
const showConfirmDialog = ref(false);
const pendingSelection = ref<{ type: 'bem' | 'hima'; id: number } | null>(null);

// Signature options and configuration
interface SignatureInstance {
    save: (format?: string) => string;
    clear: () => void;
    undo: () => void;
    toDataURL: () => string;
    isEmpty: () => boolean;
    addEventListener?: (event: string, callback: () => void) => void;
    removeEventListener?: (event: string, callback: () => void) => void;
}

const signature = ref<SignatureInstance | null>(null);
const options = reactive({
    penColor: "rgb(201, 162, 39)",
    backgroundColor: "rgb(14, 18, 55)",
});
const hasSignature = ref(false);
const isDrawing = ref(false);

watch(signature, (newSignature) => {
    if (newSignature) {
        if (newSignature.addEventListener) {
            newSignature.addEventListener('beginStroke', () => {
                isDrawing.value = true;
            });
            newSignature.addEventListener('endStroke', () => {
                isDrawing.value = false;
                checkSignature();
            });
        }
    }
});

const clear = () => {
    if (!signature.value) return;
    signature.value.clear();
    hasSignature.value = false;
};

const undo = () => {
    if (!signature.value) return;
    signature.value.undo();
    checkSignature();
};

const checkSignature = () => {
    if (!signature.value) return;
    const isEmpty = signature.value.isEmpty ? signature.value.isEmpty() : false;
    hasSignature.value = !isEmpty;
};

// Handle vote selection with confirmation
const handleVoteClick = (type: 'bem' | 'hima', kandidatId: number) => {
    pendingSelection.value = { type, id: kandidatId };
    showConfirmDialog.value = true;
};

// Confirm vote selection
const confirmVote = () => {
    if (!pendingSelection.value) return;

    if (pendingSelection.value.type === 'bem') {
        selectedBem.value = pendingSelection.value.id;
        setTimeout(() => {
            currentStep.value = props.kegiatanHima ? 'hima' : 'complete';
        }, 200);
    } else {
        selectedHima.value = pendingSelection.value.id;
        setTimeout(() => {
            currentStep.value = 'complete';
        }, 200);
    }

    isMoved.value = false;
    showConfirmDialog.value = false;
    pendingSelection.value = null;
};

// Cancel vote selection
const cancelVote = () => {
    showConfirmDialog.value = false;
    pendingSelection.value = null;
};

// Submit final vote
const submitVote = () => {
    if ((props.kegiatanBem && !selectedBem.value) || (props.kegiatanHima && !selectedHima.value) || !signature.value) return;

    if (!hasSignature.value) {
        alert('Mohon tanda tangani terlebih dahulu!');
        return;
    }

    isSubmitting.value = true;

    const dataUrl = signature.value.toDataURL();
    router.post(route('vote.store'), {
        id_kandidat_bem: selectedBem.value,
        id_kandidat_hima: selectedHima.value,
        ttd: dataUrl,
    }, {
        onSuccess: () => {
            isVoting.value = false;
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
};

const handleBeforeUnload = (e: BeforeUnloadEvent) => {
    if (isVoting.value && currentStep.value !== 'complete') {
        e.preventDefault();
    }
};

const handleInertiaNavigate = (event: any) => {
    if (isVoting.value && currentStep.value !== 'complete') {
        if (!confirm('Anda sedang dalam proses voting. Yakin ingin meninggalkan halaman? Pilihan Anda akan hilang.')) {
            event.preventDefault();
        }
    }
};

let removeInertiaListener: (() => void) | null = null;

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);
    removeInertiaListener = router.on('before', handleInertiaNavigate);
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    if (removeInertiaListener) {
        removeInertiaListener();
    }
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: currentStep.value === 'bem' ? 'Pemilihan Caka BEM' : currentStep.value === 'hima' ? 'Pemilihan Caka HIMA' : 'Konfirmasi',
        href: '/vote',
    },
];

const currentKegiatan = computed(() => {
    return currentStep.value === 'bem' ? props.kegiatanBem : props.kegiatanHima;
});

const pendingKandidat = computed(() => {
    if (!pendingSelection.value) return null;
    const kegiatan = pendingSelection.value.type === 'bem' ? props.kegiatanBem : props.kegiatanHima;
    return kegiatan?.kandidat.find(k => k.id === pendingSelection.value!.id);
});

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

const programStudiName = computed(() => {
    if (currentKegiatan.value?.programStudi?.nama) {
        return currentKegiatan.value.programStudi.nama;
    }
    if (auth.value?.user?.programStudi?.nama) {
        return auth.value.user.programStudi.nama;
    }
    if (props.kegiatanHima?.id_program_studi) {
        return handleProgramStudi(props.kegiatanHima.id_program_studi);
    }
    return 'Program Studi';
});
</script>

<template>

    <Head :title="title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-0 overflow-x-hidden">

            <!-- ===== PAGE HEADER ===== -->
            <div class="relative flex items-center justify-center py-8 px-4 overflow-hidden">
                <!-- Corner ornaments -->
                <div class="absolute top-0 left-0 w-20 h-20 opacity-20"
                    style="background: linear-gradient(135deg, rgba(201,162,39,0.4) 0%, transparent 60%); clip-path: polygon(0 0, 100% 0, 0 100%);"></div>
                <div class="absolute top-0 right-0 w-20 h-20 opacity-20"
                    style="background: linear-gradient(225deg, rgba(201,162,39,0.4) 0%, transparent 60%); clip-path: polygon(0 0, 100% 0, 100% 100%);"></div>

                <div class="relative z-10 flex flex-col items-center gap-2 text-center">
                    <img src="/Logo pemira.png" alt="Logo PEMIRA" class="size-14 drop-shadow-lg" onerror="this.style.display='none'" />
                    <p class="text-xs text-white/40 uppercase tracking-widest">Selamat Datang di Bilik Suara</p>
                    <h1 class="text-lg sm:text-xl lg:text-2xl font-black text-white uppercase poppins-font leading-tight">
                        {{ currentStep !== 'complete' ? currentKegiatan.nama : `Pemilihan Umum Raya Mahasiswa FMIPA ${dayjs().year()}` }}
                    </h1>
                    <p class="text-sm font-bold poppins-font" style="color: #f0c040;">FMIPA {{ dayjs().year() }}</p>
                </div>
            </div>

            <!-- ===== VOTING SECTION (BEM and HIMA) ===== -->
            <div v-if="currentStep !== 'complete'" class="max-w-4xl mx-auto w-full px-4 pb-12">
                <div class="flex flex-col md:flex-row gap-6 justify-center items-stretch">
                    <div v-for="kandidat in currentKegiatan.kandidat" :key="kandidat.id"
                        class="flex-1 max-w-sm mx-auto w-full">

                        <!-- Candidate Card -->
                        <div class="vote-card rounded-2xl overflow-hidden relative"
                            :class="(currentStep === 'bem' && selectedBem === kandidat.id) || (currentStep === 'hima' && selectedHima === kandidat.id) ? 'selected' : ''">

                            <!-- Vertical label -->
                            <div class="absolute left-0 top-0 bottom-0 w-10 flex items-center justify-center z-10"
                                style="background: linear-gradient(180deg, #c9a227, #8b6914);">
                                <span class="text-white text-xs font-black uppercase tracking-wider poppins-font"
                                    style="writing-mode: vertical-rl; transform: rotate(180deg); letter-spacing: 0.2em;">
                                    {{ currentStep === 'bem' ? 'CAKA BEM' : 'CAKA HIMA' }}
                                </span>
                            </div>

                            <!-- Photo area -->
                            <div class="ml-10 relative" style="min-height: 220px; background: linear-gradient(135deg, #1e2456, #2a3070);">
                                <!-- Decorative star -->
                                <div class="star-4 absolute top-3 right-3 size-6 bg-yellow-400/25"></div>

                                <img :src="kandidat.foto ? `/storage/${kandidat.foto}` : `/images/kotak-kosong.webp`"
                                    :alt="`Kandidat ${kandidat.no_urut}`"
                                    class="w-full h-56 object-cover object-top"
                                    style="mask-image: linear-gradient(to bottom, black 60%, transparent 100%);" />

                                <!-- Number badge -->
                                <div class="absolute bottom-3 left-3 size-10 rounded-full flex items-center justify-center text-white font-black text-lg poppins-font"
                                    style="background: linear-gradient(135deg, #c9a227, #f0c040); box-shadow: 0 0 15px rgba(201,162,39,0.5);">
                                    {{ kandidat.no_urut.toString().padStart(2, '0') }}
                                </div>
                            </div>

                            <!-- Candidate info -->
                            <div class="ml-10 p-4 space-y-1">
                                <div v-if="!kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')">
                                    <p class="font-bold text-white text-sm">
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                    </p>
                                    <p class="text-xs text-yellow-400/60">
                                        {{ handleProgramStudi(kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.id_program_studi!) }}'
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.angkatan.toString().slice(-2) }}
                                    </p>
                                    <p v-if="currentStep === 'bem'" class="font-semibold text-white/70 text-sm mt-1">
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                    </p>
                                    <p v-if="currentStep === 'bem'" class="text-xs text-yellow-400/60">
                                        {{ handleProgramStudi(kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.id_program_studi!) }}'
                                        {{ kandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.angkatan.toString().slice(-2) }}
                                    </p>
                                </div>
                                <p v-else class="font-bold text-white/60 text-base">Kotak Kosong</p>

                                <!-- Vote button -->
                                <div class="pt-3">
                                    <AlertDialog>
                                        <AlertDialogTrigger as-child class="w-full">
                                            <button @click="handleVoteClick(currentStep, kandidat.id)"
                                                :disabled="isMoved"
                                                class="w-full py-2.5 rounded-xl text-sm font-bold transition-all duration-300"
                                                :class="(currentStep === 'bem' && selectedBem === kandidat.id) || (currentStep === 'hima' && selectedHima === kandidat.id)
                                                    ? 'btn-gold'
                                                    : 'border border-white/20 text-white/60 hover:border-yellow-400/40 hover:text-yellow-400'">
                                                Vote {{ kandidat.no_urut.toString().padStart(2, '0') }}
                                            </button>
                                        </AlertDialogTrigger>
                                    </AlertDialog>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== COMPLETE / SUMMARY SECTION ===== -->
            <div v-else class="max-w-2xl mx-auto w-full px-4 pb-12">
                <div class="pemira-card rounded-2xl p-6 space-y-6">
                    <!-- Header -->
                    <div class="flex flex-col items-center gap-3 text-center">
                        <div class="size-14 rounded-full border-2 border-green-400/50 flex items-center justify-center">
                            <CheckCircle2 class="size-8 text-green-400" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-white">Ringkasan Pilihan Anda</h2>
                            <p class="text-sm text-white/40 mt-1">
                                Berikut ringkasan dari caka cawaka dan cakahima yang Anda pilih.
                            </p>
                        </div>
                    </div>

                    <!-- Selections grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- BEM Selection -->
                        <div v-if="kegiatanBem" class="rounded-xl p-4 border border-yellow-400/20" style="background: rgba(201,162,39,0.05);">
                            <p class="text-xs text-yellow-400/60 uppercase tracking-widest font-semibold mb-3">Pilihan Caka BEM</p>
                            <div v-if="selectedBem">
                                <p class="text-sm text-white/50">Nomor Urut {{ kegiatanBem.kandidat.find(k => k.id === selectedBem)?.no_urut }}</p>
                                <p v-if="kegiatanBem.kandidat.find(k => k.id === selectedBem)?.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')"
                                    class="font-bold text-white mt-1">Kotak Kosong</p>
                                <span v-else>
                                    <p class="font-bold text-white text-sm mt-1">
                                        {{ kegiatanBem.kandidat.find(k => k.id === selectedBem)?.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                    </p>
                                    <p class="text-sm text-white/50">
                                        {{ kegiatanBem.kandidat.find(k => k.id === selectedBem)?.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                    </p>
                                </span>
                                <p class="text-xs text-yellow-400/50 mt-1">{{ kegiatanBem.nama }}</p>
                            </div>
                        </div>

                        <!-- HIMA Selection -->
                        <div v-if="kegiatanHima" class="rounded-xl p-4 border border-yellow-400/20" style="background: rgba(201,162,39,0.05);">
                            <p class="text-xs text-yellow-400/60 uppercase tracking-widest font-semibold mb-3">Pilihan Caka HIMA</p>
                            <div v-if="selectedHima">
                                <p class="text-sm text-white/50">Nomor Urut {{ kegiatanHima.kandidat.find(k => k.id === selectedHima)?.no_urut }}</p>
                                <p v-if="kegiatanHima.kandidat.find(k => k.id === selectedHima)?.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama.includes('Kotak Kosong')"
                                    class="font-bold text-white mt-1">Kotak Kosong</p>
                                <p v-else class="font-bold text-white text-sm mt-1">
                                    {{ kegiatanHima.kandidat.find(k => k.id === selectedHima)?.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                </p>
                                <p class="text-xs text-yellow-400/50 mt-1">{{ programStudiName }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Signature area -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-white/70">
                                Tanda Tangan Presensi <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <button @click="undo" class="p-1.5 rounded-lg border border-white/10 text-white/40 hover:text-yellow-400 hover:border-yellow-400/30 transition-all">
                                    <Undo class="size-3.5" />
                                </button>
                                <button @click="clear" class="p-1.5 rounded-lg border border-white/10 text-white/40 hover:text-red-400 hover:border-red-400/30 transition-all">
                                    <X class="size-3.5" />
                                </button>
                            </div>
                        </div>
                        <div class="relative rounded-xl overflow-hidden border"
                            :class="!hasSignature ? 'border-red-500/50' : 'border-yellow-400/30'"
                            style="height: 160px; background: #0e1237;">
                            <Vue3Signature
                                ref="signature"
                                :sigOption="options"
                                :w="'100%'"
                                :h="'100%'"
                                @end="checkSignature"
                                @begin="isDrawing = true" />
                            <div v-if="!hasSignature && !isDrawing"
                                class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <p class="text-xs text-white/20 italic">Tanda tangan di sini...</p>
                            </div>
                        </div>
                        <p v-if="!hasSignature" class="text-xs text-red-400">
                            Tanda tangan presensi wajib diisi
                        </p>
                    </div>

                    <!-- Warning -->
                    <div class="rounded-xl p-4 border border-yellow-400/20" style="background: rgba(201,162,39,0.05);">
                        <div class="flex gap-2">
                            <TriangleAlert class="size-4 text-yellow-400 shrink-0 mt-0.5" />
                            <p class="text-xs text-white/50 leading-relaxed">
                                <span class="font-semibold text-yellow-400">PERHATIAN:</span>
                                Anda wajib membubuhkan tanda tangan dan menekan tombol "Kirim Pilihan" untuk mengirim pilihan Anda.
                            </p>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        @click="submitVote"
                        :disabled="isSubmitting || !hasSignature"
                        class="w-full py-3 rounded-xl text-sm font-bold btn-gold flex items-center justify-center gap-2 disabled:opacity-50">
                        <LoaderCircle v-if="isSubmitting" class="size-4 animate-spin" />
                        Kirim Pilihan
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== CONFIRMATION DIALOG ===== -->
        <AlertDialog :open="showConfirmDialog" @update:open="showConfirmDialog = $event">
            <AlertDialogContent class="bg-[#1a1f4a] border-yellow-900/30">
                <AlertDialogHeader>
                    <TriangleAlert class="size-16 text-yellow-400 mx-auto" />
                    <AlertDialogTitle class="mt-2 text-xl text-center text-white">
                        Perhatian
                    </AlertDialogTitle>
                    <AlertDialogDescription class="text-center text-white/60">
                        <p class="mb-4 text-sm">
                            Pemilihan hanya dapat dilakukan sekali dan tidak ada pengulangan.
                        </p>
                        <div v-if="pendingKandidat" class="bg-white/5 border border-yellow-400/20 p-4 rounded-xl text-left">
                            <p class="font-bold text-yellow-400 text-base mb-2">
                                Nomor Urut {{ pendingKandidat.no_urut }}
                            </p>
                            <p v-if="pendingKandidat.mahasiswa.find(m => m.pivot.jabatan)?.nama.includes('Kotak Kosong')"
                                class="text-sm text-white/70">Kotak Kosong</p>
                            <span v-else>
                                <p class="text-sm text-white/80">
                                    {{ pendingKandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.nama }}
                                    <span class="text-white/40 text-xs">
                                        ({{ handleProgramStudi(pendingKandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.id_program_studi!) }}'
                                        {{ pendingKandidat.mahasiswa.find(m => m.pivot.jabatan === 'ketua')?.angkatan.toString().slice(-2) }})
                                    </span>
                                </p>
                                <p v-if="currentStep === 'bem'" class="text-sm text-white/70 mt-1">
                                    {{ pendingKandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.nama }}
                                    <span class="text-white/40 text-xs">
                                        ({{ handleProgramStudi(pendingKandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.id_program_studi!) }}'
                                        {{ pendingKandidat.mahasiswa.find(m => m.pivot.jabatan === 'wakil')?.angkatan.toString().slice(-2) }})
                                    </span>
                                </p>
                            </span>
                        </div>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter class="sm:justify-center">
                    <div class="grid w-full grid-cols-2 gap-3">
                        <AlertDialogCancel @click="cancelVote" :disabled="isMoved"
                            class="m-0 border border-yellow-400/30 text-yellow-400 hover:bg-yellow-400/10 bg-transparent">
                            Batal
                        </AlertDialogCancel>
                        <button @click="confirmVote" :disabled="isMoved"
                            class="btn-gold py-2 rounded-lg text-sm font-bold">
                            Lanjutkan
                        </button>
                    </div>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>