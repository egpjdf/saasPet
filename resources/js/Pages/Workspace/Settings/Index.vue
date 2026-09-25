<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FeaturesTab from './FeaturesTab.vue';
import LimitsTab from './LimitsTab.vue';
import IntegrationsTab from './IntegrationsTab.vue';
import NotificationsTab from './NotificationsTab.vue';
import WebhooksTab from './WebhooksTab.vue';
import { useWorkspaceSettings } from '@/Composables/useWorkspaceSettings';
import { useTenantStore } from '@/stores/tenant';

defineProps<{
  organization: any;
  workspace: any;
}>();

const activeTab = ref<'features' | 'limits' | 'integrations' | 'notifications' | 'webhooks'>('features');

const tabs = [
  { id: 'features', label: 'Funcionalidades', icon: 'puzzle-piece' },
  { id: 'limits', label: 'Limites', icon: 'ruler' },
  { id: 'integrations', label: 'Integrações', icon: 'plug' },
  { id: 'notifications', label: 'Notificações', icon: 'bell' },
  { id: 'webhooks', label: 'Webhooks', icon: 'arrow-path' },
] as const;

const tabIcons: Record<string, any> = {
  'puzzle-piece': () => import('@heroicons/vue/24/outline/PuzzlePieceIcon'),
  'ruler': () => import('@heroicons/vue/24/outline/RulerIcon'),
  'plug': () => import('@heroicons/vue/24/outline/PlugIcon'),
  'bell': () => import('@heroicons/vue/24/outline/BellIcon'),
  'arrow-path': () => import('@heroicons/vue/24/outline/ArrowPathIcon'),
};

const { load } = useWorkspaceSettings();
const tenantStore = useTenantStore();

onMounted(() => {
  load();
});

function setActiveTab(tabId: typeof activeTab.value) {
  activeTab.value = tabId;
}
</script>

<template>
  <AppLayout :title="workspace.name + ' - Configurações'">
    <template #default>
      <Head :title="workspace.name + ' - Configurações'" />

      <div class="mb-8 flex items-center justify-between">
        <div>
          <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-2" aria-label="Breadcrumb">
            <a :href="`/${organization.slug}`" class="hover:text-gray-700">{{ organization.name }}</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <a :href="`/${organization.slug}/${workspace.slug}`" class="hover:text-gray-700">{{ workspace.name }}</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-900 font-medium">Configurações</span>
          </nav>
          <h1 class="text-3xl font-bold text-gray-900">{{ workspace.name }}</h1>
          <p class="text-gray-600 mt-1">Gerencie configurações, funcionalidades e integrações do workspace</p>
        </div>
        <div class="flex items-center space-x-2">
          <span class="px-3 py-1 text-sm font-medium text-green-600 bg-green-50 rounded-full">
            {{ workspace.status === 'active' ? 'Ativo' : 'Inativo' }}
          </span>
        </div>
      </div>

      <!-- Tab Navigation -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <nav class="flex overflow-x-auto" aria-label="Workspace Settings Tabs">
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
          <FeaturesTab v-show="activeTab === 'features'" />
          <LimitsTab v-show="activeTab === 'limits'" />
          <IntegrationsTab v-show="activeTab === 'integrations'" />
          <NotificationsTab v-show="activeTab === 'notifications'" />
          <WebhooksTab v-show="activeTab === 'webhooks'" />
        </div>
      </div>
    </template>
  </AppLayout>
</template>