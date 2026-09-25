<script setup lang="ts">
defineProps<{
    name: string;
    status: 'healthy' | 'degraded' | 'down';
    latency: string;
    detail?: string;
}>();

const statusConfig: Record<string, { color: string; icon: any }> = {
    healthy: { color: 'text-green-600', icon: () => import('@heroicons/vue/24/outline/CheckCircleIcon') },
    degraded: { color: 'text-yellow-600', icon: () => import('@heroicons/vue/24/outline/ExclamationCircleIcon') },
    down: { color: 'text-red-600', icon: () => import('@heroicons/vue/24/outline/XCircleIcon') },
};

const config = statusConfig[props.status] || statusConfig.healthy;
const IconComponent = config.icon;
</script>

<template>
    <div class="flex items-center justify-between py-2">
        <div class="flex items-center space-x-3">
            <component :is="IconComponent" :class="['w-5 h-5', config.color]" />
            <span class="font-medium text-gray-900">{{ name }}</span>
        </div>
        <div class="flex items-center space-x-4 text-sm text-gray-500">
            <span v-if="detail" class="text-gray-400">{{ detail }}</span>
            <span :class="config.color" class="font-mono">{{ latency }}</span>
        </div>
    </div>
</template>