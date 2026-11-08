<script setup lang="ts">
import Form from './Form.vue'
import { route } from 'ziggy-js'
import { cn } from '@/lib/utils'
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Input } from '@/components/ui/input'
import { Dialog, DialogContent, DialogTrigger, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button, buttonVariants } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { toast } from 'vue-sonner'
import type { ColumnDef, SortingState, ColumnFiltersState, PaginationState } from '@tanstack/vue-table'
import { CircleX, Filter, Plus, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, RefreshCw, LoaderCircle, Mail, Send, CheckCircle2, TriangleAlert } from 'lucide-vue-next'
import { FlexRender, getCoreRowModel, getSortedRowModel, getFilteredRowModel, getPaginationRowModel, useVueTable } from '@tanstack/vue-table'

// Define props untuk data mahasiswa
const props = defineProps<{
    columns: ColumnDef<any, any>[]
    data: any[]
    helpers?: {
        [key: string]: any[]
    }
}>();

// State management
const sorting = ref<SortingState>([])
const columnFiltersState = ref<ColumnFiltersState>([])
const globalFilter = ref('')
const isDialogOpen = ref(false)
const isSyncDialogOpen = ref(false)
const isViewDialogOpen = ref(false)
const isBulkDialogOpen = ref(false)
const selectedRowData = ref<any>(null)
const pagination = ref<PaginationState>({
    pageIndex: 0,
    pageSize: 10,
})

// State untuk sync mahasiswa & credentials
const isSyncing = ref(false);
const sendingNim = ref<string | null>(null);
const isBulkSending = ref(false);
const bulkOnlyUnsent = ref(true);
const bulkOnlyActive = ref(true);
const bulkProdi = ref<string>('all');
const bulkAngkatan = ref<string>('all');
const bulkCancelRequested = ref(false);
const BULK_CHUNK_SIZE = 15;

const bulkProgress = ref<{
    active: boolean;
    finished: boolean;
    stopped: boolean;
    quotaExceeded: boolean;
    targetTotal: number;
    processed: number;
    sent: number;
    failed: number;
    currentAngkatan: string | number | null;
    errorMessage: string | null;
}>({
    active: false,
    finished: false,
    stopped: false,
    quotaExceeded: false,
    targetTotal: 0,
    processed: 0,
    sent: 0,
    failed: 0,
    currentAngkatan: null,
    errorMessage: null,
});

// Computed values
const activeFilters = computed(() => {
    return columnFiltersState.value.filter(filter => filter.value !== undefined && filter.value !== '')
})

const totalWithEmail = computed(() => {
    return props.data.filter((u: any) => u.email && !u.is_admin).length;
})

const totalUnsent = computed(() => {
    return props.data.filter((u: any) => u.email && !u.credentials_sent_at && !u.is_admin).length;
})

const bulkTargetCandidates = computed(() => {
    return props.data.filter((u: any) => {
        if (u.is_admin || !u.email || String(u.email).trim() === '') return false;
        if (bulkOnlyActive.value && u.status !== 'aktif') return false;
        if (bulkOnlyUnsent.value && u.credentials_sent_at) return false;
        if (bulkProdi.value !== 'all' && String(u.id_program_studi) !== String(bulkProdi.value)) return false;
        if (bulkAngkatan.value !== 'all' && String(u.angkatan) !== String(bulkAngkatan.value)) return false;
        return true;
    });
})

const bulkProgressPercent = computed(() => {
    if (bulkProgress.value.targetTotal <= 0) return 0;
    return Math.min(100, Math.round((bulkProgress.value.processed / bulkProgress.value.targetTotal) * 100));
})

// Setup table instance
const table = useVueTable({
    get data() { return props.data },
    get columns() { return props.columns },
    getRowId: (row) => String(row.nim),
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    meta: {
        helpers: props.helpers
    },
    state: {
        get sorting() { return sorting.value },
        get columnFilters() { return columnFiltersState.value },
        get globalFilter() { return globalFilter.value },
        get pagination() { return pagination.value },
    },
    onSortingChange: (updaterOrValue) => {
        sorting.value = typeof updaterOrValue === 'function'
            ? updaterOrValue(sorting.value)
            : updaterOrValue
    },
    onColumnFiltersChange: (updaterOrValue) => {
        columnFiltersState.value = typeof updaterOrValue === 'function'
            ? updaterOrValue(columnFiltersState.value)
            : updaterOrValue
    },
    onGlobalFilterChange: (updaterOrValue) => {
        globalFilter.value = typeof updaterOrValue === 'function'
            ? updaterOrValue(globalFilter.value)
            : updaterOrValue
    },
    onPaginationChange: (updaterOrValue) => {
        pagination.value = typeof updaterOrValue === 'function'
            ? updaterOrValue(pagination.value)
            : updaterOrValue
    },
})

// Helper functions
const getFilterDisplayValue = (value: any, columnId?: string): string => {
    if (Array.isArray(value)) {
        return `${value.length} dipilih`
    }

    // Handle program studi display
    if (columnId === 'id_program_studi') {
        const prodiName = props.helpers?.programStudi?.find(ps => ps.id === Number(value))?.nama
        return prodiName || String(value)
    }

    // Handle status display
    if (columnId === 'status') {
        if (value === 'aktif') return 'Aktif'
        if (value === 'nonaktif') return 'Nonaktif'
    }

    // Handle status display
    if (columnId === 'email_verified_at') {
        if (value === 'terverifikasi') return 'Terverifikasi'
        if (value === 'belum_terverifikasi') return 'Belum Terverifikasi'
    }

    return String(value).replace(/_/g, ' ')
}

// Column filters configuration - specific untuk mahasiswa
const columnFiltersConfig = [
    {
        columnId: 'id_program_studi',
        label: 'Prodi',
        type: 'select' as const,
    },
    {
        columnId: 'angkatan',
        label: 'Angkatan',
        type: 'select' as const,
    },
    {
        columnId: 'status',
        label: 'Status',
        type: 'select' as const,
    },
    {
        columnId: 'email_verified_at',
        label: 'Verifikasi',
        type: 'select' as const,
    },
]

// Get unique values for select filters
const getUniqueValues = (columnId: string): string[] => {
    const uniqueValues = new Set<string>()
    props.data.forEach((row: any) => {
        const value = row[columnId]
        if (value != null) {
            uniqueValues.add(String(value))
        }
    })
    return Array.from(uniqueValues).sort()
}

// Get status options
const getStatusOptions = () => {
    return [
        { value: 'terverifikasi', label: 'Sudah' },
        { value: 'belum_terverifikasi', label: 'Belum' }
    ]
}

// View handler for row click
const handleRowClick = (rowData: any) => {
    selectedRowData.value = rowData
    isViewDialogOpen.value = true
}

// Function untuk sync mahasiswa
const handleSyncMahasiswa = () => {
    const currentYear = new Date().getFullYear();
    
    isSyncing.value = true;
    router.post(
        route('users.sync-data', currentYear),
        {},
        {
            onSuccess: () => {
                isSyncDialogOpen.value = false;
                isSyncing.value = false;
            },
            onError: () => {
                isSyncing.value = false;
            },
            onFinish: () => {
                isSyncing.value = false;
            }
        }
    );
};

// Function untuk send credentials ke satu mahasiswa
const handleSendCredentials = (nim: string, event: Event) => {
    event.stopPropagation(); // prevent row click from firing
    if (!confirm('Kirim ulang email credentials ke mahasiswa ini? Password lama akan direset.')) return;
    sendingNim.value = nim;
    router.post(
        route('users.send-credentials', nim),
        {},
        {
            onFinish: () => { sendingNim.value = null; }
        }
    );
};

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

const getCsrfHeaders = (): Record<string, string> => {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    const xsrfMatch = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    if (xsrfMatch && xsrfMatch[1]) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfMatch[1]);
    } else {
        const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (metaToken) {
            headers['X-CSRF-TOKEN'] = metaToken;
        }
    }
    return headers;
};

const resetBulkProgress = () => {
    bulkResumeAfterNim.value = null;
    bulkProgress.value = {
        active: false,
        finished: false,
        stopped: false,
        quotaExceeded: false,
        targetTotal: 0,
        processed: 0,
        sent: 0,
        failed: 0,
        currentAngkatan: null,
        errorMessage: null,
    };
};

const bulkResumeAfterNim = ref<string | null>(null);

const handleStopBulkSend = () => {
    bulkCancelRequested.value = true;
};

// Kirim password massal bertahap (chunked per 15 mahasiswa agar aman dari timeout server)
const handleBulkSend = async () => {
    if (isBulkSending.value) return;

    const isResuming = (bulkProgress.value.stopped || Boolean(bulkProgress.value.errorMessage)) && bulkResumeAfterNim.value !== null;

    isBulkSending.value = true;
    bulkCancelRequested.value = false;

    if (isResuming) {
        bulkProgress.value.active = true;
        bulkProgress.value.finished = false;
        bulkProgress.value.stopped = false;
        bulkProgress.value.quotaExceeded = false;
        bulkProgress.value.errorMessage = null;
    } else {
        bulkResumeAfterNim.value = null;
        bulkProgress.value = {
            active: true,
            finished: false,
            stopped: false,
            quotaExceeded: false,
            targetTotal: bulkTargetCandidates.value.length,
            processed: 0,
            sent: 0,
            failed: 0,
            currentAngkatan: null,
            errorMessage: null,
        };
    }

    let afterNim: string | null = bulkResumeAfterNim.value;
    let lockRetries = 0;
    let networkRetries = 0;

    try {
        while (!bulkCancelRequested.value) {
            let response: Response;
            try {
                response = await fetch(route('users.send-bulk-credentials'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: getCsrfHeaders(),
                    body: JSON.stringify({
                        chunked: true,
                        batch_size: BULK_CHUNK_SIZE,
                        after_nim: afterNim,
                        only_unsent: bulkOnlyUnsent.value,
                        only_active: bulkOnlyActive.value,
                        id_program_studi: bulkProdi.value === 'all' ? null : Number(bulkProdi.value),
                        angkatan: bulkAngkatan.value === 'all' ? null : Number(bulkAngkatan.value),
                    }),
                });
            } catch {
                if (networkRetries < 3) {
                    networkRetries++;
                    await sleep(2500);
                    continue;
                }
                bulkProgress.value.errorMessage = 'Koneksi jaringan terputus. Sebagian email sudah terkirim — silakan klik Kirim Sekarang lagi untuk melanjutkan sisanya.';
                break;
            }

            if (response.status === 409) {
                if (lockRetries < 5) {
                    lockRetries++;
                    await sleep(3000);
                    continue;
                }
                const lockedJson = await response.json().catch(() => null);
                bulkProgress.value.errorMessage = lockedJson?.message || 'Pengiriman email massal lain masih aktif. Coba lagi dalam beberapa detik.';
                break;
            }

            if (!response.ok) {
                if ((response.status === 502 || response.status === 503 || response.status === 504) && networkRetries < 3) {
                    networkRetries++;
                    await sleep(2500);
                    continue;
                }
                const errJson = await response.json().catch(() => null);
                bulkProgress.value.errorMessage = errJson?.message || `Server mengembalikan status ${response.status}. Silakan lanjutkan kembali.`;
                break;
            }

            lockRetries = 0;
            networkRetries = 0;

            const payload = await response.json();

            if (payload.status === 'empty') {
                bulkProgress.value.finished = true;
                break;
            }

            bulkProgress.value.sent += Number(payload.sent || 0);
            bulkProgress.value.failed += Number(payload.failed || 0);
            bulkProgress.value.processed += Number(payload.batch_total || 0);
            const dynamicTotal = bulkProgress.value.processed + Number(payload.remaining || 0);
            if (dynamicTotal > 0) {
                bulkProgress.value.targetTotal = dynamicTotal;
            }
            if (payload.last_angkatan) {
                bulkProgress.value.currentAngkatan = payload.last_angkatan;
            }

            afterNim = payload.next_after_nim || null;
            bulkResumeAfterNim.value = afterNim;

            if (payload.quota_exceeded) {
                bulkProgress.value.quotaExceeded = true;
                bulkProgress.value.errorMessage = payload.message || 'Kuota pengiriman harian Amazon SES telah habis.';
                break;
            }

            if (!payload.has_more || !afterNim) {
                bulkProgress.value.finished = true;
                bulkResumeAfterNim.value = null;
                break;
            }
        }

        if (bulkCancelRequested.value && !bulkProgress.value.finished) {
            bulkProgress.value.stopped = true;
        }
    } finally {
        isBulkSending.value = false;
        bulkProgress.value.active = false;
        router.reload({ only: ['mahasiswa'] });

        if (bulkProgress.value.quotaExceeded || bulkProgress.value.errorMessage) {
            toast.error(bulkProgress.value.errorMessage || 'Pengiriman terhenti karena kendala layanan email.');
        } else if (bulkProgress.value.stopped) {
            toast.info(`Pengiriman dihentikan sementara (${bulkProgress.value.sent} email berhasil terkirim).`);
        } else if (bulkProgress.value.finished) {
            if (bulkProgress.value.sent === 0 && bulkProgress.value.failed === 0) {
                toast.info('Tidak ada mahasiswa yang memenuhi syarat pengiriman email.');
            } else if (bulkProgress.value.failed > 0) {
                toast.warning(`Pengiriman selesai: ${bulkProgress.value.sent} berhasil, ${bulkProgress.value.failed} gagal.`);
            } else {
                toast.success(`Pengiriman massal selesai: ${bulkProgress.value.sent} email berhasil terkirim!`);
            }
        }
    }
};

watch([bulkOnlyUnsent, bulkOnlyActive, bulkProdi, bulkAngkatan], () => {
    if (!isBulkSending.value) {
        resetBulkProgress();
    }
});

watch(isBulkDialogOpen, (open) => {
    if (open && !isBulkSending.value) {
        resetBulkProgress();
    }
});

// Submit handler
const handleSuccess = () => {
    isDialogOpen.value = false
}

// Watch untuk reset selectedRowData saat dialog ditutup
watch(isViewDialogOpen, (newValue) => {
    if (!newValue) {
        selectedRowData.value = null
    }
})
</script>

<template>
    <div class="flex flex-col w-full gap-4">
        <!-- Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="space-y-1 mb-2 md:mb-4">
                <h2 class="text-xl md:text-2xl font-bold tracking-tight">Data Mahasiswa</h2>
                <p class="text-muted-foreground">Kelola data mahasiswa FMIPA</p>
            </div>

            <div class="flex items-center justify-center max-w-lg gap-4">
                <!-- Search bar -->
                <div class="flex justify-end items-center w-full">
                    <Input placeholder="Cari mahasiswa..." :model-value="globalFilter"
                        @update:model-value="table.setGlobalFilter($event)"
                        class="text-sm w-full sm:max-w-48 lg:max-w-64" />
                </div>

                <!-- Data filter -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="sm" class="ml-auto h-9">
                            <Filter class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-[200px]">
                        <div class="flex flex-col gap-3 p-2">
                            <!-- Prodi Filter -->
                            <div class="flex flex-col gap-2">
                                <Select :model-value="String(table.getColumn('id_program_studi')?.getFilterValue())"
                                    @update:model-value="(value) => table.getColumn('id_program_studi')?.setFilterValue(value === 'all' ? undefined : value)">
                                    <SelectTrigger class="text-sm capitalize text-foreground">
                                        <SelectValue placeholder="Pilih Prodi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all" class="text-foreground">
                                            Semua
                                        </SelectItem>
                                        <SelectItem v-for="value in getUniqueValues('id_program_studi')" :key="value"
                                            :value="value" class="capitalize text-foreground">
                                            {{props.helpers?.programStudi?.find(ps => ps.id === Number(value))?.nama ||
                                                value}}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Angkatan Filter -->
                            <div class="flex flex-col gap-2">
                                <Select :model-value="String(table.getColumn('angkatan')?.getFilterValue())"
                                    @update:model-value="(value) => table.getColumn('angkatan')?.setFilterValue(value === 'all' ? undefined : value)">
                                    <SelectTrigger class="text-sm capitalize text-foreground">
                                        <SelectValue placeholder="Pilih Angkatan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua
                                        </SelectItem>
                                        <SelectItem v-for="value in getUniqueValues('angkatan')" :key="value"
                                            :value="value" class="capitalize">
                                            {{ value }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Status Filter -->
                            <div class="flex flex-col gap-2">
                                <Select :model-value="String(table.getColumn('status')?.getFilterValue())"
                                    @update:model-value="(value) => table.getColumn('status')?.setFilterValue(value === 'all' ? undefined : value)">
                                    <SelectTrigger class="text-sm capitalize text-foreground">
                                        <SelectValue placeholder="Pilih Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua
                                        </SelectItem>
                                        <SelectItem v-for="value in getUniqueValues('status')" :key="value"
                                            :value="value" class="capitalize">
                                            {{ value }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Verifikasi Filter -->
                            <div class="flex flex-col gap-2">
                                <Select :model-value="String(table.getColumn('email_verified_at')?.getFilterValue())"
                                    @update:model-value="(value) => table.getColumn('email_verified_at')?.setFilterValue(value === 'all' ? undefined : value)">
                                    <SelectTrigger class="text-sm capitalize text-foreground">
                                        <SelectValue placeholder="Pilih Verifikasi" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua
                                        </SelectItem>
                                        <SelectItem v-for="option in getStatusOptions()" :key="option.value"
                                            :value="option.value" class="capitalize">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Sync Data Mahasiswa button -->
                <Dialog v-model:open="isSyncDialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline" size="default">
                            <RefreshCw class="h-4 w-4" />
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-xl">
                        <DialogHeader>
                            <DialogTitle>Sinkronisasi Data Mahasiswa</DialogTitle>
                            <DialogDescription>
                                Apakah Anda yakin ingin menyinkronkan data mahasiswa tahun {{ new Date().getFullYear() }} dari Excel?
                                <br /><br />
                                Mahasiswa baru akan <strong>otomatis dibuatkan akun</strong> dengan password acak dan dikirim ke email <code>NIM@student.unud.ac.id</code>.
                                Data yang sudah ada hanya akan diperbarui (password tidak berubah).
                            </DialogDescription>
                        </DialogHeader>

                        <!-- Dialog Actions -->
                        <div class="flex justify-end gap-2 pt-4">
                            <Button 
                                variant="outline" 
                                @click="isSyncDialogOpen = false" 
                                :disabled="isSyncing"
                            >
                                Batal
                            </Button>
                            <Button 
                                @click="handleSyncMahasiswa" 
                                :disabled="isSyncing"
                            >
                                <LoaderCircle v-if="isSyncing" class="h-4 w-4 animate-spin" />
                                Sinkronkan
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>

                <!-- Bulk Send Credentials Button & Dialog -->
                <Dialog v-model:open="isBulkDialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline" size="sm" class="h-9 gap-1.5 border-emerald-600/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/10" title="Kirim password ke email">
                            <Mail class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            <span class="text-xs font-semibold">Kirim Massal</span>
                        </Button>
                    </DialogTrigger>
                    <DialogContent
                        class="max-w-lg"
                        @interact-outside="(e) => { if (isBulkSending) e.preventDefault() }"
                        @escape-key-down="(e) => { if (isBulkSending) e.preventDefault() }"
                    >
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-2">
                                <Mail class="h-5 w-5 text-emerald-600" />
                                Kirim Password ke Mahasiswa
                            </DialogTitle>
                            <DialogDescription class="pt-1">
                                Pengiriman dilakukan bertahap otomatis (per {{ BULK_CHUNK_SIZE }} mahasiswa) agar stabil dan bebas timeout server.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-3 text-xs text-foreground">
                            <div class="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20 space-y-1">
                                <p><strong>Total Mahasiswa dengan email:</strong> {{ totalWithEmail }} orang</p>
                                <p><strong>Belum pernah dikirimi email:</strong> {{ totalUnsent }} orang</p>
                                <p><strong>Target sesuai filter di bawah:</strong> <span class="font-bold text-emerald-400">{{ bulkTargetCandidates.length }} orang</span></p>
                            </div>

                            <!-- Filter Prodi & Angkatan khusus Kirim Massal -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="space-y-1">
                                    <label class="text-[11px] font-medium text-muted-foreground">Filter Program Studi</label>
                                    <Select v-model="bulkProdi" :disabled="isBulkSending">
                                        <SelectTrigger class="h-8 text-xs">
                                            <SelectValue placeholder="Semua Prodi" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Prodi</SelectItem>
                                            <SelectItem v-for="value in getUniqueValues('id_program_studi')" :key="value" :value="value">
                                                {{ props.helpers?.programStudi?.find(ps => ps.id === Number(value))?.nama || value }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-[11px] font-medium text-muted-foreground">Filter Angkatan</label>
                                    <Select v-model="bulkAngkatan" :disabled="isBulkSending">
                                        <SelectTrigger class="h-8 text-xs">
                                            <SelectValue placeholder="Semua Angkatan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Angkatan</SelectItem>
                                            <SelectItem v-for="value in getUniqueValues('angkatan')" :key="value" :value="value">
                                                Angkatan {{ value }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div class="space-y-2 pt-1">
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="bulkOnlyUnsent"
                                        v-model="bulkOnlyUnsent"
                                        :disabled="isBulkSending"
                                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <label for="bulkOnlyUnsent" class="text-xs text-foreground font-medium cursor-pointer">
                                        Hanya kirim ke yang belum pernah menerima email
                                    </label>
                                </div>

                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="bulkOnlyActive"
                                        v-model="bulkOnlyActive"
                                        :disabled="isBulkSending"
                                        class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <label for="bulkOnlyActive" class="text-xs text-foreground font-medium cursor-pointer">
                                        Hanya mahasiswa berstatus Aktif
                                    </label>
                                </div>
                            </div>

                            <!-- Live Progress Panel -->
                            <div
                                v-if="bulkProgress.active || bulkProgress.finished || bulkProgress.stopped || bulkProgress.errorMessage"
                                class="p-3 rounded-lg border space-y-2"
                                :class="bulkProgress.errorMessage ? 'bg-red-500/10 border-red-500/30' : 'bg-white/5 border-white/10'"
                            >
                                <div class="flex items-center justify-between font-medium">
                                    <span class="flex items-center gap-1.5">
                                        <LoaderCircle v-if="bulkProgress.active" class="h-3.5 w-3.5 animate-spin text-emerald-400" />
                                        <TriangleAlert v-else-if="bulkProgress.errorMessage" class="h-3.5 w-3.5 text-red-400" />
                                        <CheckCircle2 v-else class="h-3.5 w-3.5 text-emerald-400" />
                                        <template v-if="bulkProgress.active">
                                            Mengirim email...
                                            <span v-if="bulkProgress.currentAngkatan" class="text-muted-foreground">(Angkatan {{ bulkProgress.currentAngkatan }})</span>
                                        </template>
                                        <template v-else-if="bulkProgress.stopped">Dihentikan sementara</template>
                                        <template v-else-if="bulkProgress.errorMessage">Terhenti</template>
                                        <template v-else>Pengiriman selesai</template>
                                    </span>
                                    <span>{{ bulkProgress.processed }} / {{ bulkProgress.targetTotal }} ({{ bulkProgressPercent }}%)</span>
                                </div>

                                <div class="w-full h-2 rounded-full bg-white/10 overflow-hidden">
                                    <div
                                        class="h-full bg-emerald-500 transition-all duration-300"
                                        :style="{ width: `${bulkProgressPercent}%` }"
                                    ></div>
                                </div>

                                <div class="flex items-center justify-between text-[11px] text-muted-foreground">
                                    <span class="text-emerald-400 font-medium">Berhasil: {{ bulkProgress.sent }}</span>
                                    <span :class="bulkProgress.failed > 0 ? 'text-amber-400 font-medium' : ''">Gagal: {{ bulkProgress.failed }}</span>
                                    <span>Sisa: {{ Math.max(0, bulkProgress.targetTotal - bulkProgress.processed) }}</span>
                                </div>

                                <p v-if="bulkProgress.errorMessage" class="text-[11px] text-red-300 pt-1">
                                    {{ bulkProgress.errorMessage }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-3">
                            <Button
                                v-if="isBulkSending"
                                variant="destructive"
                                @click="handleStopBulkSend"
                                :disabled="bulkCancelRequested"
                            >
                                {{ bulkCancelRequested ? 'Menghentikan...' : 'Hentikan Sementara' }}
                            </Button>
                            <Button
                                v-else
                                variant="outline"
                                @click="isBulkDialogOpen = false"
                            >
                                {{ bulkProgress.finished ? 'Tutup' : 'Batal' }}
                            </Button>
                            <Button
                                v-if="!bulkProgress.finished"
                                @click="handleBulkSend"
                                :disabled="isBulkSending || bulkTargetCandidates.length === 0"
                                class="gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white"
                            >
                                <LoaderCircle v-if="isBulkSending" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                {{ bulkProgress.stopped || bulkProgress.errorMessage ? 'Lanjutkan Pengiriman' : 'Kirim Sekarang' }}
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>

                <!-- Create button -->
                <Dialog v-model:open="isDialogOpen">
                    <DialogTrigger :class="cn(buttonVariants({ variant: 'default', size: 'default' }))">
                        <Plus class="size-4 font-bold" />
                        <span class="hidden sm:block">Tambah</span>
                    </DialogTrigger>
                    <Form mode="create" :program-studi="helpers!.programStudi" @success="handleSuccess" />
                </Dialog>
            </div>
        </div>

        <!-- Data Table -->
        <div class="border rounded-md">
            <Table>
                <!-- Active Filters -->
                <TableHeader v-if="globalFilter || activeFilters.length > 0">
                    <TableRow>
                        <TableCell :colspan="columns.length">
                            <div class="flex items-center gap-2 py-1 px-4 flex-wrap">
                                <span class="text-sm font-medium">Filter aktif:</span>

                                <!-- Global Filter Badge -->
                                <Button v-if="globalFilter" variant="secondary" size="sm" class="h-6 px-2"
                                    @click="table.setGlobalFilter('')">
                                    Pencarian: {{ globalFilter }}
                                    <CircleX class="ml-2 size-4" />
                                </Button>

                                <!-- Column Filters Badges -->
                                <template v-for="filter in activeFilters" :key="filter.id">
                                    <Button v-if="columnFiltersConfig.find(c => c.columnId === filter.id)"
                                        variant="secondary" size="sm" class="h-6 px-2"
                                        @click="table.getColumn(filter.id)?.setFilterValue(undefined)">
                                        <span class="capitalize">
                                            {{columnFiltersConfig.find(c => c.columnId === filter.id)?.label}}:
                                            {{ getFilterDisplayValue(filter.value, filter.id) }}
                                        </span>
                                        <CircleX class="size-4" />
                                    </Button>
                                </template>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableHeader>

                <!-- Table Header -->
                <TableHeader v-if="table.getRowModel().rows?.length">
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id" class="py-2">
                            <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                                :props="header.getContext()" />
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <!-- Table Body -->
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow v-for="row in table.getRowModel().rows" :key="row.id"
                            :data-state="row.getIsSelected() ? 'selected' : undefined" class="cursor-pointer"
                            @click="() => handleRowClick(row.original)">
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="truncate max-w-96">
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </TableCell>
                            <!-- Kirim Credentials button -->
                            <TableCell class="w-10 text-right" @click.stop>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs gap-1"
                                    :class="row.original.credentials_sent_at ? 'text-green-400 hover:text-green-300' : 'text-yellow-400 hover:text-yellow-300'"
                                    :disabled="sendingNim === row.original.nim"
                                    :title="row.original.credentials_sent_at ? 'Email sudah terkirim — kirim ulang?' : 'Kirim credentials'"
                                    @click="handleSendCredentials(row.original.nim, $event)"
                                >
                                    <LoaderCircle v-if="sendingNim === row.original.nim" class="h-3 w-3 animate-spin" />
                                    <Mail v-else class="h-3 w-3" />
                                    {{ row.original.credentials_sent_at ? '' : 'Kirim' }}
                                </Button>
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell :colspan="columns.length" class="h-36 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <CircleX class="size-8 text-primary/70" />
                                    <p>Tidak ada data yang ditemukan.</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="table.getRowCount() > 0" class="flex items-center mt-2 justify-between px-2">
            <div class="text-muted-foreground text-sm">
                {{ table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1 }} -
                {{
                    Math.min(
                        table.getState().pagination.pageSize * (table.getState().pagination.pageIndex + 1),
                        table.getFilteredRowModel().rows.length
                    )
                }} dari {{ table.getFilteredRowModel().rows.length }} baris.
            </div>

            <div class="flex items-center space-x-2">
                <p class="text-sm font-medium hidden sm:flex">Per halaman</p>
                <Select :model-value="`${table.getState().pagination.pageSize}`"
                    @update:model-value="(value) => table.setPageSize(Number(value))">
                    <SelectTrigger class="h-8 w-[70px]">
                        <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem v-for="pageSize in [5, 10, 25, 50, 100]" :key="pageSize" :value="`${pageSize}`">
                            {{ pageSize }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="flex items-center space-x-4 lg:space-x-6">
                <div class="hidden w-auto md:flex md:items-center md:justify-center text-sm font-medium">
                    Halaman {{ table.getState().pagination.pageIndex + 1 }} dari
                    {{ table.getPageCount() }}
                </div>
                <div class="flex items-center space-x-2">
                    <Button variant="outline" class="hidden w-8 h-8 p-0 lg:flex" :disabled="!table.getCanPreviousPage()"
                        @click="table.setPageIndex(0)">
                        <span class="sr-only">Ke halaman pertama</span>
                        <ChevronsLeft class="w-4 h-4" />
                    </Button>
                    <Button variant="outline" class="w-8 h-8 p-0" :disabled="!table.getCanPreviousPage()"
                        @click="table.previousPage()">
                        <span class="sr-only">Ke halaman sebelumnya</span>
                        <ChevronLeft class="w-4 h-4" />
                    </Button>
                    <Button variant="outline" class="w-8 h-8 p-0" :disabled="!table.getCanNextPage()"
                        @click="table.nextPage()">
                        <span class="sr-only">Ke halaman selanjutnya</span>
                        <ChevronRight class="w-4 h-4" />
                    </Button>
                    <Button variant="outline" class="hidden w-8 h-8 p-0 lg:flex" :disabled="!table.getCanNextPage()"
                        @click="table.setPageIndex(table.getPageCount() - 1)">
                        <span class="sr-only">Ke halaman terakhir</span>
                        <ChevronsRight class="w-4 h-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- View Dialog - Terpisah dari loop -->
        <Dialog v-model:open="isViewDialogOpen">
            <Form v-if="selectedRowData" mode="view" :mahasiswa-data="selectedRowData"
                :program-studi="helpers!.programStudi" @success="isViewDialogOpen = false" />
        </Dialog>
    </div>
</template>