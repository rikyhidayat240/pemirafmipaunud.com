import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface FlashMessage {
    success?: string;
    error?: string;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    alert: {title: string; message: string, type: string} | null
};

export interface ProgramStudi {
    id: number;
    kode: string;
    nama: string;
}

export interface User {
    nim: string;
    nama: string;
    email: string;
    avatar?: string;
    angkatan: number;
    is_admin: number | boolean;
    id_program_studi: number;
    email_verified_at: string | null;
    status: 'aktif' | 'nonaktif';
    created_at: string;
    updated_at: string;
    programStudi: ProgramStudi;
}

export interface Kegiatan {
    id: number;
    id_program_studi: number;
    nama: string;
    tahun: number;
    waktu_mulai: Date | string;
    waktu_selesai: Date | string;
    foto: string;
    ruang_lingkup: 'fakultas' | 'program studi';
    programStudi: ProgramStudi;
    total_mahasiswa?: number;
    jumlah_pemilih?: number;
}

export interface Kandidat {
    id: number;
    id_kegiatan: number;
    no_urut: string;
    foto: string;
    visi: string;
    misi: string;
    jumlah_suara: number;
    kegiatan: Kegiatan;
    mahasiswa: (User & { pivot: { jabatan: 'ketua' | 'wakil' } })[];
}

export interface SuratSuara {
    id: number;
    id_kegiatan: number;
    nim: string;
    has_vote: number | boolean;
    kegiatan: Kegiatan;
    mahasiswa: User;
}

export type BreadcrumbItemType = BreadcrumbItem;
