<script setup lang="ts">
defineProps<{
    icon: string;
    title: string;
    description: string;
}>();

const icons: Record<string, any> = {
    calendar: () => import('@heroicons/vue/24/outline/CalendarDaysIcon'),
    pets: () => import('@heroicons/vue/24/outline/PawPrintIcon'),
    pos: () => import('@heroicons/vue/24/outline/CreditCardIcon'),
    inventory: () => import('@heroicons/vue/24/outline/CubeTransparentIcon'),
    ai: () => import('@heroicons/vue/24/outline/SparklesIcon'),
    reports: () => import('@heroicons/vue/24/outline/ChartBarIcon'),
};

const IconComponent = icons[props.icon] || icons.calendar;
</script>

<template>
    <div class="bg-white rounded-xl p-8 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
            <component :is="IconComponent" class="w-6 h-6 text-primary-600" />
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ title }}</h3>
        <p class="text-gray-600">{{ description }}</p>
    </div>
</template>