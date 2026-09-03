<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Kegiatan } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const props = defineProps<{
  kegiatans?: Kegiatan[] | null
}>()

// Palet warna unik per HIMA (aksen berbeda, tapi tetap cocok di dark theme)
const himaColors = [
  { from: '#1e3a8a', to: '#3b82f6', accent: '#60a5fa', glow: 'rgba(59,130,246,0.25)' },
  { from: '#064e3b', to: '#10b981', accent: '#34d399', glow: 'rgba(16,185,129,0.25)' },
  { from: '#4c1d95', to: '#8b5cf6', accent: '#a78bfa', glow: 'rgba(139,92,246,0.25)' },
  { from: '#78350f', to: '#f59e0b', accent: '#fcd34d', glow: 'rgba(245,158,11,0.25)' },
  { from: '#881337', to: '#f43f5e', accent: '#fb7185', glow: 'rgba(244,63,94,0.25)' },
  { from: '#0c4a6e', to: '#06b6d4', accent: '#67e8f9', glow: 'rgba(6,182,212,0.25)' },
]

const selectedHimaId = ref<string>('')

const processedKegiatans = computed(() => {
  if (!props.kegiatans) return []
  return props.kegiatans.map(kegiatan => {
    const totalSuara = kegiatan.kandidat?.reduce((acc, val) => acc + val.jumlah_suara, 0) || 0
    const kandidat = kegiatan.kandidat?.map(calon => ({
      ...calon,
      jumlah_suara_persen: totalSuara > 0
        ? parseFloat((calon.jumlah_suara / totalSuara * 100).toFixed(1))
        : 0
    })) || []
    return { ...kegiatan, totalSuara, kandidat }
  })
})

if (processedKegiatans.value.length > 0) {
  selectedHimaId.value = processedKegiatans.value[0].id.toString()
}

const selectedKegiatan = computed(() =>
  processedKegiatans.value.find(k => k.id.toString() === selectedHimaId.value)
)

const selectedIndex = computed(() =>
  processedKegiatans.value.findIndex(k => k.id.toString() === selectedHimaId.value)
)

const colors = computed(() => himaColors[selectedIndex.value % himaColors.length])

const isWinner = (persen: number) => {
  if (!selectedKegiatan.value?.kandidat) return false
  const max = Math.max(...selectedKegiatan.value.kandidat.map((c: any) => c.jumlah_suara_persen))
  return persen === max && max > 0
}

const isVotingEnded = computed(() => {
  if (!selectedKegiatan.value?.waktu_selesai) return false
  return new Date(selectedKegiatan.value.waktu_selesai) < new Date()
})

const badgeLabel = computed(() => isVotingEnded.value ? '✦ Terpilih' : '✦ Unggul')
</script>

<template>
  <Head title="Hasil Pemilihan HIMA" />
  <AppLayout>
    <div class="min-h-full pb-16">

      <!-- Empty State -->
      <div v-if="processedKegiatans.length === 0"
        class="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center px-4">
        <img src="/Logo pemira.png" alt="" class="size-24 opacity-10">
        <h1 class="text-xl font-bold text-white/30">Belum Ada Data Pemilihan HIMA</h1>
      </div>

      <template v-else>
        <!-- Header Banner -->
        <div class="relative overflow-hidden mb-2 pt-6 pb-4 text-center">
          <!-- BG glow -->
          <div class="absolute inset-0 pointer-events-none transition-all duration-700"
            :style="{ background: `radial-gradient(ellipse 80% 60% at 50% 0%, ${colors.glow}, transparent 70%)` }"></div>
          <!-- Logo watermark -->
          <img src="/Logo pemira.png" alt=""
            class="absolute inset-0 w-full h-full object-contain opacity-[0.04] scale-110 pointer-events-none">

          <!-- Corner ornaments -->
          <div class="absolute top-0 left-0 w-28 opacity-20 pointer-events-none">
            <svg viewBox="0 0 120 120" fill="none"><line x1="40" y1="0" x2="0" y2="40" stroke="#c9a227" stroke-width="1"/><line x1="80" y1="0" x2="0" y2="80" stroke="#c9a227" stroke-width="0.7"/><line x1="115" y1="0" x2="0" y2="115" stroke="#c9a227" stroke-width="0.5"/></svg>
          </div>
          <div class="absolute top-0 right-0 w-28 opacity-20 pointer-events-none" style="transform: scaleX(-1)">
            <svg viewBox="0 0 120 120" fill="none"><line x1="40" y1="0" x2="0" y2="40" stroke="#c9a227" stroke-width="1"/><line x1="80" y1="0" x2="0" y2="80" stroke="#c9a227" stroke-width="0.7"/><line x1="115" y1="0" x2="0" y2="115" stroke="#c9a227" stroke-width="0.5"/></svg>
          </div>

          <div class="relative z-10 px-4">
            <p class="text-xs text-yellow-400/60 uppercase tracking-[0.3em] font-semibold mb-3">Hasil Pemilihan Umum Raya</p>
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-black text-white uppercase tracking-wide">
              Hasil Pemilihan HIMA
            </h1>
          </div>
        </div>

        <!-- Dropdown Pilih HIMA -->
        <div class="max-w-2xl mx-auto px-4 mb-8">
          <Select v-model="selectedHimaId">
            <SelectTrigger
              class="w-full font-semibold text-sm h-11 rounded-xl transition-all"
              :style="{
                background: 'rgba(255,255,255,0.04)',
                border: `1.5px solid ${colors.accent}55`,
                color: colors.accent
              }">
              <SelectValue placeholder="Pilih Himpunan Mahasiswa (HIMA)" />
            </SelectTrigger>
            <SelectContent class="bg-[#141836] border-yellow-900/30">
              <SelectGroup>
                <SelectLabel class="text-yellow-400/50 text-xs uppercase tracking-widest">Daftar Pemilihan HIMA</SelectLabel>
                <SelectItem
                  v-for="kegiatan in processedKegiatans"
                  :key="kegiatan.id"
                  :value="kegiatan.id.toString()"
                  class="text-white/80 focus:text-yellow-400 focus:bg-white/5">
                  {{ kegiatan.nama }}
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>

        <!-- Hasil HIMA yang dipilih -->
        <div v-if="selectedKegiatan" class="max-w-3xl mx-auto px-4">

          <!-- Header banner HIMA -->
          <div class="relative rounded-2xl overflow-hidden mb-6 py-8 text-center"
            :style="{ background: `linear-gradient(135deg, ${colors.from}cc, ${colors.to}99)` }"
            style="border: 1px solid rgba(255,255,255,0.1);">
            <img src="/Logo pemira.png" alt=""
              class="absolute inset-0 w-full h-full object-contain opacity-10 scale-110 pointer-events-none">
            <div class="relative z-10 px-4">
              <h2 class="text-xl sm:text-2xl font-black text-white uppercase tracking-wide drop-shadow-lg">
                {{ selectedKegiatan.nama }}
              </h2>
              <div class="inline-flex items-center gap-2 mt-2 px-3 py-1 rounded-full text-sm font-semibold"
                style="background: rgba(0,0,0,0.3); color: white;">
                <span class="size-1.5 rounded-full bg-white animate-pulse inline-block"></span>
                {{ selectedKegiatan.totalSuara }} Suara Masuk
              </div>
            </div>
          </div>

          <!-- Kartu Kandidat -->
          <div class="space-y-4">
            <div
              v-for="(calon, i) in selectedKegiatan.kandidat"
              :key="calon.id"
              class="relative rounded-2xl overflow-hidden transition-all duration-300 pemira-card"
              :class="isWinner(calon.jumlah_suara_persen) ? 'scale-[1.01]' : 'opacity-75'"
              :style="isWinner(calon.jumlah_suara_persen)
                ? `border-color: ${colors.accent}99; box-shadow: 0 0 28px ${colors.glow};`
                : 'border-color: rgba(255,255,255,0.06);'"
            >
              <!-- Winner glow overlay -->
              <div v-if="isWinner(calon.jumlah_suara_persen)"
                class="absolute inset-0 pointer-events-none rounded-2xl"
                :style="{ background: `linear-gradient(135deg, ${colors.from}22 0%, transparent 60%)` }">
              </div>

              <!-- Badge Unggul -->
              <div v-if="isWinner(calon.jumlah_suara_persen)"
                class="absolute top-3 right-3 z-10 text-xs font-bold px-3 py-1 rounded-full text-white"
                :style="{ background: `linear-gradient(90deg, ${colors.from}, ${colors.to})` }">
                {{ badgeLabel }}
              </div>

              <div class="flex items-center gap-4 p-4 sm:p-5">
                <!-- Foto Kandidat -->
                <div class="flex-shrink-0 relative">
                  <div class="w-20 h-24 sm:w-24 sm:h-28 rounded-xl overflow-hidden"
                    :style="{ background: `linear-gradient(180deg, ${colors.from}, ${colors.to})` }">
                    <img
                      :src="`/storage/${calon.foto}`"
                      class="w-full h-full object-cover object-top"
                      alt="">
                  </div>
                  <!-- Nomor urut -->
                  <div class="absolute -bottom-2 -right-2 w-7 h-7 rounded-full flex items-center justify-center text-white font-bold text-xs"
                    :style="{ background: colors.to }">
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

                  <!-- Horizontal Progress Bar -->
                  <div class="flex items-center gap-3">
                    <div class="flex-1 h-4 rounded-full overflow-hidden" style="background: rgba(255,255,255,0.07);">
                      <div
                        class="h-full rounded-full transition-all duration-700"
                        :style="{
                          width: `${calon.jumlah_suara_persen}%`,
                          background: isWinner(calon.jumlah_suara_persen)
                            ? `linear-gradient(90deg, ${colors.from}, ${colors.accent})`
                            : 'rgba(255,255,255,0.2)'
                        }">
                      </div>
                    </div>
                    <span class="font-bold text-sm sm:text-base w-16 text-right flex-shrink-0"
                      :style="{ color: isWinner(calon.jumlah_suara_persen) ? colors.accent : 'rgba(255,255,255,0.4)' }">
                      {{ calon.jumlah_suara_persen }}%
                    </span>
                  </div>

                  <p class="text-xs text-white/30 mt-1.5">{{ calon.jumlah_suara }} suara</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </template>
    </div>
  </AppLayout>
</template>
