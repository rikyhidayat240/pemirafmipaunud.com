<script setup lang="ts">
import dayjs from 'dayjs';
</script>

<template>
    <footer class="pemira-footer w-full py-4 mt-auto">
        <div class="mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 px-4 md:max-w-7xl">
            <div class="flex items-center gap-2">
                <img src="/logo-pemira.webp" alt="Logo PEMIRA" width="24" height="24" loading="lazy" decoding="async" class="size-6 opacity-60 object-contain" onerror="this.src='/Logo pemira.png'" />
                <span class="text-xs text-white/40 font-medium">
                    PEMIRA LM FMIPA {{ dayjs().year() }}
                </span>
            </div>
            <p class="text-xs text-white/30 text-center">
                Dewan Perwakilan Mahasiswa · Fakultas Matematika dan Ilmu Pengetahuan Alam
            </p>
        </div>
    </footer>
</template>