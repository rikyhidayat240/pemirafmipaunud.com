<script setup lang="ts">
defineProps<{
    type?: 'button' | 'submit' | 'reset';
    className?: string;
}>();
</script>

<template>
    <button :type="type || 'button'" class="star-btn-animated" :class="className">
        <span class="relative z-10 flex items-center justify-center gap-1.5">
            <slot />
        </span>

        <!-- SVG Stars for Uiverse Explosion Effect -->
        <span class="absolute inset-0 pointer-events-none overflow-visible">
            <svg class="star-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 784.11 815.53">
                <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.99,29.37 371.15,197.7 392.05,407.75 20.9,-210.05 184.06,-378.38 392.06,-407.75 -208,-29.37 -371.16,-197.7 -392.06,-407.78z"/>
            </svg>
            <svg class="star-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 784.11 815.53">
                <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.99,29.37 371.15,197.7 392.05,407.75 20.9,-210.05 184.06,-378.38 392.06,-407.75 -208,-29.37 -371.16,-197.7 -392.06,-407.78z"/>
            </svg>
            <svg class="star-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 784.11 815.53">
                <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.99,29.37 371.15,197.7 392.05,407.75 20.9,-210.05 184.06,-378.38 392.06,-407.75 -208,-29.37 -371.16,-197.7 -392.06,-407.78z"/>
            </svg>
            <svg class="star-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 784.11 815.53">
                <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.99,29.37 371.15,197.7 392.05,407.75 20.9,-210.05 184.06,-378.38 392.06,-407.75 -208,-29.37 -371.16,-197.7 -392.06,-407.78z"/>
            </svg>
            <svg class="star-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 784.11 815.53">
                <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.99,29.37 371.15,197.7 392.05,407.75 20.9,-210.05 184.06,-378.38 392.06,-407.75 -208,-29.37 -371.16,-197.7 -392.06,-407.78z"/>
            </svg>
            <svg class="star-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 784.11 815.53">
                <path class="fil0" d="M392.05 0c-20.9,210.08 -184.06,378.41 -392.05,407.78 207.99,29.37 371.15,197.7 392.05,407.75 20.9,-210.05 184.06,-378.38 392.06,-407.75 -208,-29.37 -371.16,-197.7 -392.06,-407.78z"/>
            </svg>
        </span>
    </button>
</template>
