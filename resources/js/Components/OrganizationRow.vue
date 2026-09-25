<script setup lang="ts">
defineProps<{
    name: string;
    slug: string;
    workspaces: number;
    users: number;
    status: string;
    plan: string;
}>();

const statusColors: Record<string, string> = {
    active: 'bg-green-50 text-green-700',
    trial: 'bg-yellow-50 text-yellow-700',
    suspended: 'bg-red-50 text-red-700',
    cancelled: 'bg-gray-50 text-gray-700',
};
</script>

<template>
    <div class="px-6 py-4 hover:bg-gray-50">
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-gray-900">{{ name }}</p>
                <p class="text-sm text-gray-500">{{ slug }}</p>
            </div>
            <div class="flex items-center space-x-4 text-sm">
                <span class="text-gray-600">{{ workspaces }} WS</span>
                <span class="text-gray-600">{{ users }} users</span>
                <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusColors[status] || statusColors.active]">
                    {{ status }}
                </span>
                <span class="px-2 py-1 text-xs font-medium bg-primary-50 text-primary-700 rounded">{{ plan }}</span>
            </div>
        </div>
    </div>
</template>