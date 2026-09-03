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
</script>

<template>
  <Head title="Hasil Pemilihan BEM" />
  <AppLayout>
    <div class="min-h-full pb-16">

      <!-- Header Banner BEM -->
      <div class="relative overflow-hidden mb-6 pt-6 pb-8 text-center">
        <!-- Background glow -->
        <div class="absolute inset-0 pointer-events-none"
          style="background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(201,162,39,0.15) 0%, transparent 70%);"></div>

        <!-- Logo watermark -->
        <img src="/Logo pemira.png" alt=""
          class="absolute inset-0 w-full h-full object-contain opacity-[0.04] scale-110 pointer-events-none">

        <!-- Corner gold lines -->
        <div class="absolute top-0 left-0 w-32 opacity-25 pointer-events-none">
          <svg viewBox="0 0 130 130" fill="none"><line x1="40" y1="0" x2="0" y2="40" stroke="#c9a227" stroke-width="1"/><line x1="80" y1="0" x2="0" y2="80" stroke="#c9a227" stroke-width="0.7"/><line x1="120" y1="0" x2="0" y2="120" stroke="#c9a227" stroke-width="0.5"/></svg>
        </div>
        <div class="absolute top-0 right-0 w-32 opacity-25 pointer-events-none" style="transform: scaleX(-1)">
          <svg viewBox="0 0 130 130" fill="none"><line x1="40" y1="0" x2="0" y2="40" stroke="#c9a227" stroke-width="1"/><line x1="80" y1="0" x2="0" y2="80" stroke="#c9a227" stroke-width="0.7"/><line x1="120" y1="0" x2="0" y2="120" stroke="#c9a227" stroke-width="0.5"/></svg>
        </div>

        <div class="relative z-10 px-4">
          <!-- Label -->
          <p class="text-xs text-yellow-400/60 uppercase tracking-[0.3em] font-semibold mb-3">Hasil Pemilihan Umum Raya</p>
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-white uppercase tracking-wide mb-2">
            {{ kegiatan?.nama ?? 'Hasil Pemilihan BEM' }}
          </h1>
          <!-- Stats bar -->
          <div v-if="kegiatan" class="inline-flex items-center gap-2 mt-3 px-4 py-1.5 rounded-full text-sm font-semibold"
            style="background: rgba(201,162,39,0.15); border: 1px solid rgba(201,162,39,0.3); color: #f0c040;">
            <span class="size-1.5 rounded-full bg-yellow-400 animate-pulse inline-block"></span>
            Total {{ totalSuara }} Suara Masuk
          </div>
        </div>
      </div>

      <!-- Kandidat cards -->
      <div v-if="kegiatan && kandidat.length > 0" class="max-w-3xl mx-auto px-4 space-y-4">
        <div
          v-for="(calon, i) in kandidat"
          :key="calon.id"
          class="relative rounded-2xl overflow-hidden transition-all duration-300 pemira-card"
          :class="isWinner(calon.jumlah_suara_persen) ? 'scale-[1.01]' : 'opacity-80'"
          :style="isWinner(calon.jumlah_suara_persen)
            ? 'border-color: rgba(201,162,39,0.6); box-shadow: 0 0 30px rgba(201,162,39,0.25);'
            : 'border-color: rgba(255,255,255,0.07);'"
        >
          <!-- Winner glow overlay -->
          <div v-if="isWinner(calon.jumlah_suara_persen)"
            class="absolute inset-0 pointer-events-none rounded-2xl"
            style="background: linear-gradient(135deg, rgba(201,162,39,0.08) 0%, transparent 60%);"></div>

          <!-- Badge -->
          <div v-if="isWinner(calon.jumlah_suara_persen)"
            class="absolute top-3 right-3 z-10 text-xs font-bold px-3 py-1 rounded-full"
            style="background: linear-gradient(90deg, #b8860b, #f0c040); color: #111635;">
            {{ badgeLabel }}
          </div>

          <div class="flex items-center gap-4 p-4 sm:p-5">
            <!-- Foto -->
            <div class="flex-shrink-0 relative">
              <div class="w-20 h-24 sm:w-24 sm:h-28 rounded-xl overflow-hidden"
                style="background: linear-gradient(180deg, #1a1f4a, #0f1229);">
                <img :src="`/storage/${calon.foto}`"
                  class="w-full h-full object-cover object-top" alt="">
              </div>
              <!-- Nomor urut -->
              <div class="absolute -bottom-2 -right-2 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold"
                style="background: linear-gradient(135deg, #b8860b, #f0c040); color: #111635;">
                {{ i + 1 }}
              </div>
            </div>

            <!-- Info & Bar -->
            <div class="flex-1 min-w-0">
              <h3 class="font-bold text-base sm:text-lg text-white truncate">
                {{ calon.mahasiswa![0].nama }}
              </h3>
              <p class="text-sm text-white/40 mb-4">
                {{ calon.mahasiswa![0].programStudi?.nama ?? calon.mahasiswa![0].program_studi?.nama }}
                &middot; Angkatan {{ calon.mahasiswa![0].angkatan.toString().substring(2, 4) }}
              </p>

              <!-- Progress Bar -->
              <div class="flex items-center gap-3">
                <div class="flex-1 h-4 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.07);">
                  <div
                    class="h-full rounded-full transition-all duration-700"
                    :style="{
                      width: `${calon.jumlah_suara_persen}%`,
                      background: isWinner(calon.jumlah_suara_persen)
                        ? 'linear-gradient(90deg, #b8860b, #f0c040)'
                        : 'linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0.35))'
                    }">
                  </div>
                </div>
                <span class="font-bold text-sm sm:text-base w-16 text-right flex-shrink-0"
                  :style="{ color: isWinner(calon.jumlah_suara_persen) ? '#f0c040' : 'rgba(255,255,255,0.5)' }">
                  {{ calon.jumlah_suara_persen }}%
                </span>
              </div>

              <p class="text-xs text-white/30 mt-1.5">{{ calon.jumlah_suara }} suara</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="flex flex-col items-center justify-center py-24 gap-4 text-center">
        <img src="/Logo pemira.png" alt="" class="size-20 opacity-10">
        <p class="text-lg font-semibold text-white/30">Belum ada data pemilihan BEM saat ini.</p>
      </div>

    </div>
  </AppLayout>
</template>
