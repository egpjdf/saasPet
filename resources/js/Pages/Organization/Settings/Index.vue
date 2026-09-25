<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BrandingTab from './BrandingTab.vue';
import DomainTab from './DomainTab.vue';
import LocalizationTab from './LocalizationTab.vue';
import FeaturesTab from './FeaturesTab.vue';
import EmailTab from './EmailTab.vue';
import { useOrganizationSettings } from '@/Composables/useOrganizationSettings';

defineProps<{
  organization: any;
}>();

const activeTab = ref<'branding' | 'domain' | 'localization' | 'features' | 'email'>('branding');

const tabs = [
  { id: 'branding', label: 'Identidade Visual', icon: 'paint-brush' },
  { id: 'domain', label: 'Domínio', icon: 'globe-alt' },
  { id: 'localization', label: 'Localização', icon: 'map' },
  { id: 'features', label: 'Funcionalidades', icon: 'puzzle-piece' },
  { id: 'email', label: 'E-mail', icon: 'envelope' },
] as const;

const tabIcons: Record<string, any> = {
  'paint-brush': () => import('@heroicons/vue/24/outline/PaintBrushIcon'),
  'globe-alt': () => import('@heroicons/vue/24/outline/GlobeAltIcon'),
  'map': () => import('@heroicons/vue/24/outline/MapIcon'),
  'puzzle-piece': () => import('@heroicons/vue/24/outline/PuzzlePieceIcon'),
  'envelope': () => import('@heroicons/vue/24/outline/EnvelopeIcon'),
};

const { load } = useOrganizationSettings();

onMounted(() => {
  load();
});

function setActiveTab(tabId: typeof activeTab.value) {
  activeTab.value = tabId;
}
</script>

<template>
  <AppLayout :title="organization.name + ' - Configurações'">
    <template #default>
      <Head :title="organization.name + ' - Configurações'" />

      <div class="mb-8 flex items-center justify-between">
        <div>
          <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-2" aria-label="Breadcrumb">
            <a :href="`/${organization.slug}`" class="hover:text-gray-700">{{ organization.name }}</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-900 font-medium">Configurações da Organização</span>
          </nav>
          <h1 class="text-3xl font-bold text-gray-900">{{ organization.name }}</h1>
          <p class="text-gray-600 mt-1">Gerencie identidade visual, domínio, localização, funcionalidades e e-mail</p>
        </div>
        <div class="flex items-center space-x-2">
          <span class="px-3 py-1 text-sm font-medium text-green-600 bg-green-50 rounded-full">
            {{ organization.status === 'active' ? 'Ativo' : 'Inativo' }}
          </span>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <nav class="flex overflow-x-auto" aria-label="Organization Settings Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="setActiveTab(tab.id as typeof activeTab)"
            :class="[
              'flex items-center space-x-2 px-6 py-4 border-b-2 font-medium text-sm whitespace-nowrap transition-colors',
              activeTab === tab.id
                ? 'border-primary-500 text-primary-600 bg-primary-50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
            :aria-selected="activeTab === tab.id"
            role="tab"
          >
            <component :is="tabIcons[tab.icon]" class="w-5 h-5" />
            <span>{{ tab.label }}</span>
          </button>
        </nav>

        <!-- Tab Panels -->
        <div class="p-6">
          <BrandingTab v-show="activeTab === 'branding'" />
          <DomainTab v-show="activeTab === 'domain'" />
          <LocalizationTab v-show="activeTab === 'localization'" />
          <FeaturesTab v-show="activeTab === 'features'" />
          <EmailTab v-show="activeTab === 'email'" />
        </div>
      </div>
    </template>
  </AppLayout>
</template>