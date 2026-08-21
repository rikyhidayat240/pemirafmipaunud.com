import { h } from 'vue'
import Form from './Form.vue'
import { Kandidat, Kegiatan, User } from '@/types'
import { ArrowUpDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { ColumnDef } from '@tanstack/vue-table'
import DropdownAction from '@/components/DataTableDropdown.vue'

// Declare module untuk extend TableMeta
declare module '@tanstack/vue-table' {
    interface TableMeta<TData extends unknown> {
        helpers?: {
            [key: string]: any
        }
    }
}

// Definisi kolom untuk tabel kegiatan
export const columns: ColumnDef<Kandidat>[] = [
    {
        accessorKey: 'foto',
        header: () => h('div', { class: 'text-center' }, 'Foto'),
        cell: ({ row }) => {
            const foto = row.getValue('foto') as string | undefined
            return h('div', { class: 'flex justify-center' }, [
                h('img', {
                    src: foto ? `/storage/${foto}` : '/images/blank-profile-picture.webp',
                    alt: 'Foto Kandidat',
                    class: 'h-10 w-10 rounded-md object-contain',
                }),
            ])
        }
    },
    {
        accessorKey: 'no_urut',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["No.", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => h('div', { class: 'text-center font-semibold' }, row.getValue('no_urut')),
    },
    {
        accessorKey: 'id_kegiatan',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
            }, () => ["Kegiatan", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row, table }) => {
            const idKegiatan = row.getValue('id_kegiatan') as number;

            if (idKegiatan) {
                const helpers = table.options.meta?.helpers as { kegiatan?: Kegiatan[] } | undefined;
                let kegiatanName = helpers?.kegiatan?.find(k => k.id === idKegiatan)?.nama || 'N/A';
                if (kegiatanName.includes('Pemilihan')) {
                    kegiatanName = kegiatanName.replace('Pemilihan ', '');
                }

                const colorMap: { [key: number]: string } = {
                    1: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                    2: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                    3: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    4: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    5: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                    6: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    0: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
                };
                const colorClass = colorMap[idKegiatan % 7] || 'bg-gray-100 text-gray-800';

                return h('span', { class: `${colorClass} px-2 py-1 rounded-md font-medium` }, kegiatanName);
            } else {
                return h('span', { class: 'px-2 py-1 bg-gray-100 text-gray-800 rounded-md font-medium' }, 'N/A');
            }
        },
        filterFn: (row, id, value) => {
            return String(row.getValue(id)) === String(value);
        },
    },
    {
        accessorKey: 'mahasiswa',
        header: () => h('div', { class: 'text-left' }, 'Mahasiswa'),
        cell: ({ row }) => {
            const kandidat = row.original;
            const mahasiswaKandidat = kandidat.mahasiswa || [];

            if (mahasiswaKandidat.length === 0) {
                return h('div', { class: 'text-left text-muted-foreground' }, 'Tidak ada mahasiswa');
            }

            // Sort by jabatan (ketua first, then wakil)
            const sortedMahasiswa = [...mahasiswaKandidat].sort((a, b) => {
                if (a.pivot?.jabatan === 'ketua') return -1;
                if (b.pivot?.jabatan === 'ketua') return 1;
                return 0;
            });

            return h('div', { class: 'text-left space-y-1' },
                sortedMahasiswa.map(mk =>
                    h('div', { class: 'truncate text-sm' }, [
                        h('span', { class: 'font-medium' }, mk.nama),
                        h('span', { class: 'text-muted-foreground' }, ` (${(mk.pivot?.jabatan || '').charAt(0).toUpperCase() + (mk.pivot?.jabatan || '').slice(1)})`)
                    ])
                )
            );
        },
    },
    {
        accessorKey: 'visi',
        header: () => h('div', { class: 'text-left' }, 'Visi'),
        cell: ({ row }) => {
            const visi = row.getValue('visi') as string;
            const formattedVisi = visi.replace(/(\d+\.\s)/g, '\n$1').trim();
            return h('div', { class: 'text-left max-w-64' },
                h('pre', { class: 'text-sm text-wrap font-sans line-clamp-5' }, formattedVisi)
            );
        },
    },
    {
        accessorKey: 'misi',
        header: () => h('div', { class: 'text-left' }, 'Misi'),
        cell: ({ row }) => {
            const misi = row.getValue('misi') as string;
            // Create ordered list for misi
            const misiItems = misi.split(/\d+\.\s/).filter(item => item.trim());
            return h('div', { class: 'text-left' },
                h('ol', { class: 'list-decimal list-inside space-y-1' },
                    misiItems.map(item => h('li', { class: 'text-sm truncate max-w-80' }, item.trim()))
                )
            );
        },
    },
    {
        accessorKey: 'jumlah_suara',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["Suara", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row, table }) => {
            const jumlahSuara = row.original.jumlah_suara || 0;
            const kegiatan = table.options.meta?.helpers?.kegiatan as Kegiatan[] | undefined;
            const totalMahasiswa = kegiatan?.find(k => k.id === row.original.id_kegiatan)?.total_mahasiswa || 0;
            const percentage = totalMahasiswa > 0 ? ((jumlahSuara / totalMahasiswa) * 100).toFixed(1) : '0.0';

            return h('div', { class: 'text-center' }, [
                h('div', { class: 'font-semibold' }, `${jumlahSuara} / ${totalMahasiswa}`),
                h('div', { class: 'text-xs text-muted-foreground' }, `${percentage}%`)
            ]);
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row, table }) => {
            const kandidat = row.original;
            const helpers = table.options.meta?.helpers as { kegiatan?: Kegiatan[], mahasiswa?: User[], kandidat?: Kandidat[] } | undefined;

            return h(DropdownAction, {
                data: kandidat,
                deleteRoute: route('candidates.destroy', kandidat.id),
            }, {
                // Slot untuk edit form
                'edit-form': ({ data, close }: { data: Kandidat, close: () => void }) =>
                    h(Form, {
                        mode: 'edit',
                        mahasiswa: helpers?.mahasiswa || [],
                        kegiatan: helpers?.kegiatan || [],
                        kandidat: helpers?.kandidat || [],
                        kandidatData: data,
                        onSuccess: () => {
                            close();
                        }
                    })
            });
        },
    },
]