<script setup>
import { computed } from 'vue';

const props = defineProps({
    severity: {
        type: String,
        required: true,
    },
    showTimeline: {
        type: Boolean,
        default: false,
    },
});

const config = computed(() => {
    switch (props.severity?.toUpperCase()) {
        case 'MAJOR':
        case 'CRITICAL':
            return {
                label: 'Major',
                timeline: '15 - 30 hari',
                style: 'bg-red-50 text-red-700 border-red-200 font-medium',
            };
        case 'MEDIUM':
            return {
                label: 'Medium',
                timeline: '8 - 14 hari',
                style: 'bg-amber-50 text-amber-800 border-amber-200 font-medium',
            };
        case 'MINOR':
        case 'OBSERVATION':
            return {
                label: 'Minor',
                timeline: '3 - 7 hari',
                style: 'bg-emerald-50 text-emerald-800 border-emerald-200 font-medium',
            };
        default:
            return {
                label: props.severity || '—',
                timeline: '',
                style: 'bg-gray-50 text-gray-600 border-gray-200',
            };
    }
});
</script>

<template>
    <span
        class="inline-block px-2 py-0.5 rounded text-[11px] border"
        :class="config.style"
    >
        {{ config.label }}
        <span v-if="showTimeline && config.timeline" class="text-gray-500 font-normal ml-1">({{ config.timeline }})</span>
    </span>
</template>
