import { h } from 'vue'
import Form from './Form.vue'
import { ProgramStudi, Kegiatan } from '@/types'
import { ArrowUpDown, ChartColumn } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { ColumnDef } from '@tanstack/vue-table'
import { router } from '@inertiajs/vue3'
import DropdownAction from '@/components/DataTableDropdown.vue'
import { DropdownMenuItem } from '@/components/ui/dropdown-menu'

// Declare module untuk extend TableMeta
declare module '@tanstack/vue-table' {
    interface TableMeta<TData extends unknown> {
        helpers?: {
            [key: string]: any
        }
    }
}

// Definisi kolom untuk tabel kegiatan
export const columns: ColumnDef<Kegiatan>[] = [
    {
        accessorKey: 'foto',
        header: () => h('div', { class: 'text-center' }, 'Foto'),
        cell: ({ row }) => {
            const foto = row.getValue('foto') as string | undefined
            return h('div', { class: 'flex justify-center' }, [
                h('img', {
                    src: foto ? `/storage/${foto}` : '/images/blank-photo-icon.jpg',
                    alt: 'Foto Kegiatan',
                    class: 'h-10 w-10 rounded-md object-cover',
                }),
            ])
        }
    },
    {
        accessorKey: 'nama',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-start",
            }, () => ["Nama", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => h('div', { class: 'text-left truncate' }, row.getValue('nama')),
    },
    {
        accessorKey: "tahun",
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["Tahun", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => {
            const tahun = row.getValue("tahun") as number;
            const colorClasses = [
                "text-center bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
                "text-center bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
                "text-center bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
                "text-center bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
            ];
            const colorClass = colorClasses[tahun % 4];
            return h("span", { class: `${colorClass} px-2 py-1 rounded-md font-medium flex place-self-center mx-auto` }, tahun);
        },
    },
    {
        accessorKey: 'ruang_lingkup',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["Ruang Lingkup", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => h('div', { class: 'text-center capitalize' }, row.getValue('ruang_lingkup')),
    },
    {
        accessorKey: 'id_program_studi',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["Prodi", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row, table }) => {
            const idProgramStudi = row.getValue('id_program_studi') as number;

            if (idProgramStudi) {
                const helpers = table.options.meta?.helpers as { programStudi?: ProgramStudi[] } | undefined;
                const programStudiName = helpers?.programStudi?.find(ps => ps.id === idProgramStudi)?.nama || 'N/A';

                const colorMap: { [key: number]: string } = {
                    1: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                    2: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    3: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    4: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    5: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    6: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                };
                const colorClass = colorMap[idProgramStudi] || 'bg-gray-100 text-gray-800';

                return h('span', { class: `${colorClass} px-2 py-1 rounded-md font-medium flex place-self-center mx-auto` }, programStudiName);
            } else {
                return h('span', { class: 'px-2 py-1 bg-gray-100 text-gray-800 rounded-md font-medium flex place-self-center mx-auto' }, 'N/A');
            }

        },
        filterFn: (row, id, value) => {
            return String(row.getValue(id)) === String(value);
        },
    },
    {
        accessorKey: 'waktu_mulai',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["Waktu Mulai", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => {
            const waktuMulai = row.getValue('waktu_mulai') as string
            const date = new Date(waktuMulai)
            const formatted = new Intl.DateTimeFormat('id-ID', {
                day: 'numeric',
                month: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date)
            return h('div', { class: 'text-center' }, formatted)
        },
    },
    {
        accessorKey: 'waktu_selesai',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["Waktu Selesai", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => {
            const waktuSelesai = row.getValue('waktu_selesai') as string
            const date = new Date(waktuSelesai)
            const formatted = new Intl.DateTimeFormat('id-ID', {
                day: 'numeric',
                month: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date)
            return h('div', { class: 'text-center' }, formatted)
        },
    },
    {
        accessorKey: 'jumlah_pemilih',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["Jumlah Pemilih", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => {
            const jumlahPemilih = row.original.jumlah_pemilih || 0;
            const totalMahasiswa = row.original.total_mahasiswa || 0;
            const percentage = totalMahasiswa > 0 ? ((jumlahPemilih / totalMahasiswa) * 100).toFixed(1) : '0.0';

            return h('div', { class: 'text-center' }, [
                h('div', { class: 'font-semibold' }, `${jumlahPemilih} / ${totalMahasiswa}`),
                h('div', { class: 'text-xs text-muted-foreground' }, `${percentage}%`)
            ]);
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row, table }) => {
            const kegiatan = row.original;
            const helpers = table.options.meta?.helpers as { programStudi?: ProgramStudi[] } | undefined;

            return h(DropdownAction, {
                data: kegiatan,
                deleteRoute: route('events.destroy', kegiatan.id),
            }, {
                // Slot untuk edit form
                'edit-form': ({ data, close }: { data: Kegiatan, close: () => void }) =>
                    h(Form, {
                        mode: 'edit',
                        programStudi: helpers?.programStudi || [],
                        kegiatanData: data,
                        onSuccess: () => {
                            close();
                        }
                    }),

                // Custom actions BEFORE default actions
                'custom-actions-before': ({ data }: { data: Kegiatan }) => [
                    h(DropdownMenuItem, {
                        class: 'flex items-center cursor-pointer',
                        onClick: () => {
                            router.visit(route('events.show', data.id));
                        }
                    }, () => [
                        h(ChartColumn, { class: 'w-4 h-4 mr-1' }),
                        'Lihat Hasil'
                    ])
                ],

                // // Custom actions AFTER default actions
                // 'custom-actions-after': ({ data }: { data: Kegiatan }) => [
                //     h(DropdownMenuItem, {
                //         class: 'flex items-center cursor-pointer',
                //         onClick: () => {
                //             router.visit(route('events.voters', data.id));
                //         }
                //     }, () => [
                //         h(Users, { class: 'w-4 h-4 mr-2' }),
                //         'Lihat Pemilih'
                //     ])
                // ]
            });
        },
    },
]