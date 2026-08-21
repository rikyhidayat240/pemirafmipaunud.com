<script setup lang="ts">
import Form from './Form.vue'
import Print from './Print.vue'
import { cn } from '@/lib/utils'
import { ref, computed, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Dialog, DialogTrigger } from '@/components/ui/dialog'
import { Button, buttonVariants } from '@/components/ui/button'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import type { ColumnDef, SortingState, ColumnFiltersState, PaginationState } from '@tanstack/vue-table'
import { CircleX, Filter, Plus, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Printer } from 'lucide-vue-next'
import { FlexRender, getCoreRowModel, getSortedRowModel, getFilteredRowModel, getPaginationRowModel, useVueTable } from '@tanstack/vue-table'

// Define props untuk data kegiatan
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
const isViewDialogOpen = ref(false)
const isPrintDialogOpen = ref(false)
const selectedRowData = ref<any>(null)
const pagination = ref<PaginationState>({
    pageIndex: 0,
    pageSize: 10,
})

// Computed values
const activeFilters = computed(() => {
    return columnFiltersState.value.filter(filter => filter.value !== undefined && filter.value !== '')
})

// Setup table instance
const table = useVueTable({
    get data() { return props.data },
    get columns() { return props.columns },
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

    return String(value).replace(/_/g, ' ')
}

// Column filters configuration - specific untuk kegiatan
const columnFiltersConfig = [
    {
        columnId: 'id_program_studi',
        label: 'Prodi',
        type: 'select' as const,
    },
    {
        columnId: 'tahun',
        label: 'Tahun',
        type: 'select' as const,
    },
    {
        columnId: 'ruang_lingkup',
        label: 'Ruang Lingkup',
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

// View handler for row click
const handleRowClick = (rowData: any) => {
    selectedRowData.value = rowData
    isViewDialogOpen.value = true
}

// Submit handler untuk create
const handleSuccess = () => {
    isDialogOpen.value = false
    isPrintDialogOpen.value = false
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
                <h2 class="text-xl md:text-2xl font-bold tracking-tight">Data Kegiatan</h2>
                <p class="text-muted-foreground">Kelola data kegiatan Pemira FMIPA</p>
            </div>

            <div class="flex items-center justify-center max-w-lg gap-4">
                <!-- Search bar -->
                <div class="flex justify-end items-center w-full">
                    <Input placeholder="Cari kegiatan..." :model-value="globalFilter"
                        @update:model-value="table.setGlobalFilter($event)"
                        class="text-sm w-full sm:max-w-48 lg:max-w-64" />
                </div>

                <!-- Data filter -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="outline" size="icon-sm" class="ml-auto h-9">
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

                            <!-- Tahun Filter -->
                            <div class="flex flex-col gap-2">
                                <Select :model-value="String(table.getColumn('tahun')?.getFilterValue())"
                                    @update:model-value="(value) => table.getColumn('tahun')?.setFilterValue(value === 'all' ? undefined : value)">
                                    <SelectTrigger class="text-sm capitalize text-foreground">
                                        <SelectValue placeholder="Pilih Tahun" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua
                                        </SelectItem>
                                        <SelectItem v-for="value in getUniqueValues('tahun')" :key="value"
                                            :value="value" class="capitalize">
                                            {{ value }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Ruang Lingkup Filter -->
                            <div class="flex flex-col gap-2">
                                <Select :model-value="String(table.getColumn('ruang_lingkup')?.getFilterValue())"
                                    @update:model-value="(value) => table.getColumn('ruang_lingkup')?.setFilterValue(value === 'all' ? undefined : value)">
                                    <SelectTrigger class="text-sm capitalize text-foreground">
                                        <SelectValue placeholder="Pilih Ruang Lingkup" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">
                                            Semua
                                        </SelectItem>
                                        <SelectItem v-for="value in getUniqueValues('ruang_lingkup')" :key="value"
                                            :value="value" class="capitalize">
                                            {{ value }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Print Absensi Button -->
                <Dialog v-model:open="isPrintDialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline" size="default">
                            <Printer class="h-4 w-4" />
                        </Button>
                    </DialogTrigger>
                    <Print :data="data" @success="handleSuccess" />
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
            <Form v-if="selectedRowData" mode="view" :kegiatan-data="selectedRowData"
                :program-studi="helpers!.programStudi" @success="isViewDialogOpen = false" />
        </Dialog>
    </div>
</template>