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

// Palet warna unik per HIMA: [from, to, accent]
const himaColors = [
  { from: '#1e40af', to: '#3b82f6', accent: '#60a5fa', glow: 'rgba(59,130,246,0.4)' },   // Biru - Kimia/Fisika
  { from: '#065f46', to: '#10b981', accent: '#34d399', glow: 'rgba(16,185,129,0.4)' },   // Hijau - Biologi
  { from: '#7c3aed', to: '#a78bfa', accent: '#c4b5fd', glow: 'rgba(167,139,250,0.4)' },  // Ungu - Matematika
  { from: '#b45309', to: '#f59e0b', accent: '#fcd34d', glow: 'rgba(245,158,11,0.4)' },   // Kuning - Informatika
  { from: '#be123c', to: '#f43f5e', accent: '#fb7185', glow: 'rgba(244,63,94,0.4)' },    // Pink - Farmasi
  { from: '#0e7490', to: '#06b6d4', accent: '#67e8f9', glow: 'rgba(6,182,212,0.4)' },    // Cyan - lainnya
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
        class="w-full flex justify-center items-center relative py-20 overflow-hidden">
        <img src="/logo-pemira.svg" alt="" class="h-64 opacity-10 absolute">
        <h1 class="text-2xl font-bold text-center text-white uppercase relative z-10 px-4"
          style="text-shadow:-2px -2px 0 #A50000,2px -2px 0 #A50000,-2px 2px 0 #A50000,2px 2px 0 #A50000">
          Belum Ada Data Pemilihan HIMA
        </h1>
      </div>

      <template v-else>
        <!-- Dropdown Pilih HIMA -->
        <div class="max-w-2xl mx-auto px-4 pt-8 pb-4">
          <Select v-model="selectedHimaId">
            <SelectTrigger class="w-full font-semibold text-base border-2"
              :style="{ borderColor: colors.accent, color: colors.accent }">
              <SelectValue placeholder="Pilih Himpunan Mahasiswa (HIMA)" />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                <SelectLabel>Daftar Pemilihan HIMA</SelectLabel>
                <SelectItem
                  v-for="kegiatan in processedKegiatans"
                  :key="kegiatan.id"
                  :value="kegiatan.id.toString()">
                  {{ kegiatan.nama }}
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>

        <!-- Konten Hasil HIMA -->
        <div v-if="selectedKegiatan" class="max-w-4xl mx-auto px-4">

          <!-- Header Banner HIMA -->
          <div class="relative rounded-2xl overflow-hidden mb-8 py-10"
            :style="{ background: `linear-gradient(135deg, ${colors.from}, ${colors.to})` }">
            <img src="/logo-pemira.svg" alt=""
              class="absolute inset-0 w-full h-full object-contain opacity-10 scale-110">
            <div class="relative z-10 text-center">
              <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white uppercase tracking-wide drop-shadow-lg">
                {{ selectedKegiatan.nama }}
              </h1>
              <p class="text-white/80 mt-2 font-medium">
                Total Suara Masuk: <span class="font-bold text-white text-lg">{{ selectedKegiatan.totalSuara }}</span>
              </p>
            </div>
          </div>

          <!-- Kartu Kandidat -->
          <div class="space-y-5">
            <div
              v-for="(calon, i) in selectedKegiatan.kandidat"
              :key="calon.id"
              class="relative rounded-2xl overflow-hidden border transition-all duration-300"
              :class="isWinner(calon.jumlah_suara_persen) ? 'shadow-lg scale-[1.01]' : 'opacity-80'"
              :style="{
                borderColor: isWinner(calon.jumlah_suara_persen) ? colors.accent : 'transparent',
                boxShadow: isWinner(calon.jumlah_suara_persen) ? `0 0 24px ${colors.glow}` : 'none',
                background: 'var(--color-card)'
              }">

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
                  <div class="absolute -bottom-2 -right-2 w-7 h-7 rounded-full flex items-center justify-center text-white font-bold text-sm"
                    :style="{ background: colors.to }">
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
                          background: `linear-gradient(90deg, ${colors.from}, ${colors.to})`
                        }">
                      </div>
                    </div>
                    <span class="font-bold text-sm sm:text-base w-16 text-right flex-shrink-0"
                      :style="{ color: colors.accent }">
                      {{ calon.jumlah_suara_persen }}%
                    </span>
                  </div>

                  <p class="text-xs opacity-50 mt-1">{{ calon.jumlah_suara }} suara</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </template>
    </div>
  </AppLayout>
</template>
