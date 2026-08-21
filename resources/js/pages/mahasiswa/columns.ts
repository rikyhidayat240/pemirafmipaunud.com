import { h } from 'vue'
import Form from './Form.vue'
import { route } from 'ziggy-js'
import { ProgramStudi, User } from '@/types'
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

// Definisi kolom untuk tabel mahasiswa
export const columns: ColumnDef<User>[] = [
    {
        accessorKey: 'avatar',
        header: () => h('div', { class: 'text-center' }, 'Profil'),
        cell: ({ row }) => {
            const avatar = row.getValue('avatar') as string | undefined
            return h('div', { class: 'flex justify-center' }, [
                h('img', {
                    src: avatar ? `/storage/${avatar}` : '/images/blank-profile-picture.webp',
                    alt: 'Profil',
                    class: 'h-10 w-10 rounded-md object-cover',
                }),
            ])
        }
    },
    {
        accessorKey: 'nim',
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["NIM", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('nim')),
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
        },
        filterFn: (row, id, value) => {
            return String(row.getValue(id)) === String(value);
        },
    },
    {
        accessorKey: "email",
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
            }, () => ["Email", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => h("div", { class: "text-left truncate" }, row.getValue("email") ?? "Belum terdaftar"),
    },
    {
        accessorKey: "angkatan",
        header: ({ column }) => {
            return h(Button, {
                variant: "ghost",
                onClick: () => column.toggleSorting(column.getIsSorted() === "asc"),
                class: "flex place-self-center",
            }, () => ["Akt", h(ArrowUpDown, { class: "h-4 w-4" })])
        },
        cell: ({ row }) => {
            const angkatan = row.getValue("angkatan") as number;
            const colorClasses = [
                "text-center bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
                "text-center bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
                "text-center bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
                "text-center bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
            ];
            const colorClass = colorClasses[angkatan % 4];
            return h("span", { class: `${colorClass} px-2 py-1 rounded-md font-medium flex place-self-center mx-auto` }, angkatan);
        },
    },
    {
        accessorKey: 'status',
        header: () => h('div', { class: 'text-center' }, 'Status'),
        cell: ({ row }) => {
            const status = row.getValue('status') as string
            if (status === 'aktif') {
                return h('span', { class: 'px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-md font-medium flex place-self-center mx-auto' }, 'Aktif')
            } else {
                return h('span', { class: 'px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-md font-medium flex place-self-center mx-auto' }, 'Nonaktif')
            }
        },
        filterFn: (row, id, value) => {
            return String(row.getValue(id)) === String(value);
        },
    },
    {
        accessorKey: 'email_verified_at',
        header: () => h('div', { class: 'text-center' }, 'Verifikasi'),
        cell: ({ row }) => {
            const verifiedAt = row.getValue('email_verified_at') as string | null
            if (verifiedAt) {
                return h('span', { class: 'px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-md font-medium flex place-self-center' }, 'Sudah')
            } else {
                return h('span', { class: 'px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-md font-medium flex place-self-center' }, 'Belum')
            }
        },
        filterFn: (row, id, value) => {
            const verifiedAt = row.getValue(id) as string | null;
            if (value === 'terverifikasi') {
                return verifiedAt !== null;
            } else if (value === 'belum_terverifikasi') {
                return verifiedAt === null;
            }
            return true;
        },
    },
    {
        id: "actions",
        enableHiding: false,
        cell: ({ row, table }) => {
            const mahasiswa = row.original;
            const helpers = table.options.meta?.helpers as { programStudi?: ProgramStudi[] } | undefined;

            return h(DropdownAction, {
                data: mahasiswa,
                deleteRoute: route('users.destroy', mahasiswa.nim),
            }, {
                // Slot untuk edit form
                'edit-form': ({ data, close }: { data: User, close: () => void }) => 
                    h(Form, {
                        mode: 'edit',
                        programStudi: helpers?.programStudi || [],
                        mahasiswaData: data,
                        onSuccess: () => {
                            close();
                        }
                    })
            });
        },
    },
]