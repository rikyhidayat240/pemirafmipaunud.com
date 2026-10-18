<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img src="/logo-pemira.webp" alt="Logo PEMIRA" width="40" height="40" decoding="async" class="max-w-10 max-h-10 object-contain" onerror="this.src='/Logo pemira.png'" />
</template>
