<?php

namespace App\Http\Requests;

use App\Models\Kandidat;
use App\Models\Kegiatan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_kandidat_bem'  => ['nullable', 'integer', 'exists:kandidat,id'],
            'id_kandidat_hima' => ['nullable', 'integer', 'exists:kandidat,id'],
            'ttd'              => ['required', 'string', 'max:700000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            // a) User bukan admin dan berstatus aktif
            if ($user->is_admin) {
                $validator->errors()->add('user', 'Admin tidak berhak melakukan pemilihan.');
                return;
            }

            if (! $user->isActive()) {
                $validator->errors()->add('user', 'Anda harus terdaftar sebagai mahasiswa aktif untuk melakukan pemilihan.');
                return;
            }

            $bemInput = $this->input('id_kandidat_bem');
            $himaInput = $this->input('id_kandidat_hima');

            // Setidaknya harus memilih satu kandidat
            if (! $bemInput && ! $himaInput) {
                $validator->errors()->add('kandidat', 'Tidak ada kandidat yang dipilih.');
                return;
            }

            // e) Kandidat BEM dan HIMA tidak boleh sama (cast ke integer agar "5" vs "05" tertangkap)
            if ($bemInput !== null && $himaInput !== null && (int) $bemInput === (int) $himaInput) {
                $validator->errors()->add('id_kandidat_hima', 'Kandidat BEM dan HIMA tidak boleh sama.');
                return;
            }

            // Validasi kandidat BEM
            if ($bemInput !== null) {
                $kandidatBem = Kandidat::with('kegiatan')->find((int) $bemInput);
                if (! $kandidatBem || ! $kandidatBem->kegiatan) {
                    $validator->errors()->add('id_kandidat_bem', 'Data kegiatan kandidat BEM tidak ditemukan.');
                    return;
                }

                $kegiatanBem = $kandidatBem->kegiatan;

                // d) BEM berlingkup fakultas
                if ($kegiatanBem->ruang_lingkup !== 'fakultas') {
                    $validator->errors()->add('id_kandidat_bem', 'Kandidat yang dipilih bukan untuk pemilihan tingkat fakultas (BEM).');
                    return;
                }

                // b) Jadwal kegiatan
                if (now()->isBefore($kegiatanBem->waktu_mulai)) {
                    $validator->errors()->add('id_kandidat_bem', 'Pemilihan BEM belum dimulai.');
                    return;
                }
                if (now()->isAfter($kegiatanBem->waktu_selesai)) {
                    $validator->errors()->add('id_kandidat_bem', 'Pemilihan BEM telah berakhir.');
                    return;
                }
            }

            // Validasi kandidat HIMA
            if ($himaInput !== null) {
                $kandidatHima = Kandidat::with('kegiatan')->find((int) $himaInput);
                if (! $kandidatHima || ! $kandidatHima->kegiatan) {
                    $validator->errors()->add('id_kandidat_hima', 'Data kegiatan kandidat HIMA tidak ditemukan.');
                    return;
                }

                $kegiatanHima = $kandidatHima->kegiatan;

                // d) HIMA berlingkup program studi & sesuai id_program_studi pemilih (cast integer)
                if ($kegiatanHima->ruang_lingkup !== 'program studi') {
                    $validator->errors()->add('id_kandidat_hima', 'Kandidat yang dipilih bukan untuk pemilihan tingkat program studi (HIMA).');
                    return;
                }

                if ((int) $kegiatanHima->id_program_studi !== (int) $user->id_program_studi) {
                    $validator->errors()->add('id_kandidat_hima', 'Kandidat HIMA yang dipilih bukan berasal dari program studi Anda.');
                    return;
                }

                // b) Jadwal kegiatan
                if (now()->isBefore($kegiatanHima->waktu_mulai)) {
                    $validator->errors()->add('id_kandidat_hima', 'Pemilihan HIMA belum dimulai.');
                    return;
                }
                if (now()->isAfter($kegiatanHima->waktu_selesai)) {
                    $validator->errors()->add('id_kandidat_hima', 'Pemilihan HIMA telah berakhir.');
                    return;
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'ttd.required' => 'Tanda tangan wajib diisi.',
            'ttd.max'      => 'Ukuran data tanda tangan melebihi batas yang diizinkan.',
        ];
    }
}
