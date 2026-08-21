<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Kegiatan } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
  kegiatan?: Kegiatan | null
}>()

const totalSuara = props.kegiatan?.kandidat?.reduce((acc, val) => acc + val.jumlah_suara, 0) || 0

const kandidat = computed(() => {
  return props.kegiatan?.kandidat?.map(calon => ({
    ...calon,
    jumlah_suara_persen: totalSuara > 0
      ? parseFloat((calon.jumlah_suara / totalSuara * 100).toFixed(1))
      : 0
  })) || []
})

const isWinner = (persen: number) => {
  if (!kandidat.value.length) return false
  const max = Math.max(...kandidat.value.map(c => c.jumlah_suara_persen))
  return persen === max && max > 0
}

const isVotingEnded = computed(() => {
  if (!props.kegiatan?.waktu_selesai) return false
  return new Date(props.kegiatan.waktu_selesai) < new Date()
})

const badgeLabel = computed(() => isVotingEnded.value ? '✦ Terpilih' : '✦ Unggul')

// Warna BEM: merah-kuning khas Pemira FMIPA
const bemColors = {
  from: '#991b1b',
  to: '#dc2626',
  accent: '#f87171',
  glow: 'rgba(220,38,38,0.4)',
}
</script>

<template>
  <Head title="Hasil Pemilihan BEM" />
  <AppLayout>
    <div class="min-h-full pb-16">

      <!-- Header Banner BEM -->
      <div class="relative overflow-hidden mb-8 py-12"
        :style="{ background: `linear-gradient(135deg, ${bemColors.from}, ${bemColors.to})` }">
        <img src="/logo-pemira.svg" alt=""
          class="absolute inset-0 w-full h-full object-contain opacity-10 scale-110">
        <div class="relative z-10 text-center px-4">
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white uppercase tracking-wide drop-shadow-lg">
            {{ kegiatan?.nama ?? 'Belum Ada Data Pemilihan BEM' }}
          </h1>
          <p v-if="kegiatan" class="text-white/80 mt-2 font-medium">
            Total Suara Masuk: <span class="font-bold text-white text-lg">{{ totalSuara }}</span>
          </p>
        </div>
      </div>

      <!-- Kandidat -->
      <div v-if="kegiatan && kandidat.length > 0" class="max-w-4xl mx-auto px-4 space-y-5">
        <div
          v-for="(calon, i) in kandidat"
          :key="calon.id"
          class="relative rounded-2xl overflow-hidden border transition-all duration-300"
          :class="isWinner(calon.jumlah_suara_persen) ? 'shadow-lg scale-[1.01]' : 'opacity-80'"
          :style="{
            borderColor: isWinner(calon.jumlah_suara_persen) ? bemColors.accent : 'transparent',
            boxShadow: isWinner(calon.jumlah_suara_persen) ? `0 0 24px ${bemColors.glow}` : 'none',
            background: 'var(--color-card)'
          }">

          <!-- Badge Unggul -->
          <div v-if="isWinner(calon.jumlah_suara_persen)"
            class="absolute top-3 right-3 z-10 text-xs font-bold px-3 py-1 rounded-full text-white"
            :style="{ background: `linear-gradient(90deg, ${bemColors.from}, ${bemColors.to})` }">
            {{ badgeLabel }}
          </div>

          <div class="flex items-center gap-4 p-4 sm:p-5">
            <!-- Foto -->
            <div class="flex-shrink-0 relative">
              <div class="w-20 h-24 sm:w-24 sm:h-28 rounded-xl overflow-hidden"
                :style="{ background: `linear-gradient(180deg, ${bemColors.from}, ${bemColors.to})` }">
                <img :src="`/storage/${calon.foto}`"
                  class="w-full h-full object-cover object-top" alt="">
              </div>
              <!-- Nomor urut -->
              <div class="absolute -bottom-2 -right-2 w-7 h-7 rounded-full flex items-center justify-center text-white font-bold text-sm"
                :style="{ background: bemColors.to }">
                {{ i + 1 }}
              </div>
            </div>

            <!-- Info & Bar -->
            <div class="flex-1 min-w-0">
              <h3 class="font-bold text-base sm:text-lg truncate">
                {{ calon.mahasiswa![0].nama }}
              </h3>
              <p class="text-sm opacity-60 mb-3">
                {{ calon.mahasiswa![0].programStudi?.nama ?? calon.mahasiswa![0].program_studi?.nama }}
                &middot; Angkatan {{ calon.mahasiswa![0].angkatan.toString().substring(2, 4) }}
              </p>

              <!-- Horizontal Progress Bar -->
              <div class="flex items-center gap-3">
                <div class="flex-1 h-5 rounded-full bg-black/10 dark:bg-white/10 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-700"
                    :style="{
                      width: `${calon.jumlah_suara_persen}%`,
                      background: `linear-gradient(90deg, ${bemColors.from}, ${bemColors.to})`
                    }">
                  </div>
                </div>
                <span class="font-bold text-sm sm:text-base w-16 text-right flex-shrink-0"
                  :style="{ color: bemColors.accent }">
                  {{ calon.jumlah_suara_persen }}%
                </span>
              </div>

              <p class="text-xs opacity-50 mt-1">{{ calon.jumlah_suara }} suara</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-20 opacity-50">
        <p class="text-lg font-semibold">Belum ada data pemilihan BEM saat ini.</p>
      </div>

    </div>
  </AppLayout>
</template>
