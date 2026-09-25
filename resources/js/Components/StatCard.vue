<script setup lang="ts">
defineProps<{
    title: string;
    value: string;
    icon: string;
    color: string;
}>();

const colors: Record<string, string> = {
    blue: 'bg-blue-100 text-blue-600',
    green: 'bg-green-100 text-green-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    purple: 'bg-purple-100 text-purple-600',
    red: 'bg-red-100 text-red-600',
};

const icons: Record<string, any> = {
    building: () => import('@heroicons/vue/24/outline/BuildingOfficeIcon'),
    cube: () => import('@heroicons/vue/24/outline/CubeIcon'),
    users: () => import('@heroicons/vue/24/outline/UsersIcon'),
    currency: () => import('@heroicons/vue/24/outline/CurrencyDollarIcon'),
};

const IconComponent = icons[props.icon] || icons.building;
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">{{ title }}</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ value }}</p>
            </div>
            <div :class="['p-3 rounded-lg', colors[color] || colors.blue]">
                <component :is="IconComponent" class="w-6 h-6" />
            </div>
        </div>
    </div>
</template>