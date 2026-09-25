<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useFeatureFlags } from '@/Composables/useFeatureFlags';
import type { FeatureFlagWithStatus } from '@/Composables/useFeatureFlags';
import { useNotificationStore } from '@/Stores/notification';

const {
  featuresByCategory,
  currentPlan,
  planLabels,
  planColors,
  categoryLabels,
  categoryIcons,
  loading,
  error,
  canEnableFeature,
  getUpgradeTooltip,
  load,
  clearError,
} = useFeatureFlags();

const notificationStore = useNotificationStore();

const toggling = ref<Record<string, boolean>>({});

onMounted(() => {
  load();
});

const categoryOrder = ['core', 'communication', 'analytics', 'integrations', 'automation', 'branding', 'email'] as const;

async function handleToggle(feature: FeatureFlagWithStatus) {
  if (toggling.value[feature.key]) return;
  if (!feature.enabled && !canEnableFeature.value(feature)) return;

  toggling.value[feature.key] = true;

  try {
    // In a real implementation, this would call an API
    // For now, we'll simulate the toggle
    feature.enabled = !feature.enabled;
    notificationStore.success(`${feature.enabled ? 'Ativada' : 'Desativada'}: ${feature.name}`);
  } catch {
    notificationStore.error('Erro ao alterar funcionalidade');
    feature.enabled = !feature.enabled; // Revert
  } finally {
    toggling.value[feature.key] = false;
  }
}

function isToggling(featureKey: string): boolean {
  return !!toggling.value[featureKey];
}

function getIconComponent(iconName: string) {
  const icons: Record<string, any> = {
    cube: () => import('@heroicons/vue/24/outline/CubeIcon'),
    'chat-bubble-left-right': () => import('@heroicons/vue/24/outline/ChatBubbleLeftRightIcon'),
    'chart-bar': () => import('@heroicons/vue/24/outline/ChartBarIcon'),
    'puzzle-piece': () => import('@heroicons/vue/24/outline/PuzzlePieceIcon'),
    bolt: () => import('@heroicons/vue/24/outline/BoltIcon'),
    'paint-brush': () => import('@heroicons/vue/24/outline/PaintBrushIcon'),
    envelope: () => import('@heroicons/vue/24/outline/EnvelopeIcon'),
  };
  return icons[iconName] || icons.cube;
}

function getCategoryColor(category: string): string {
  const colors: Record<string, string> = {
    core: 'text-primary-600',
    communication: 'text-green-600',
    analytics: 'text-blue-600',
    integrations: 'text-purple-600',
    automation: 'text-yellow-600',
    branding: 'text-pink-600',
    email: 'text-indigo-600',
  };
  return colors[category] || 'text-primary-600';
}

function getCategoryBg(category: string): string {
  const colors: Record<string, string> = {
    core: 'bg-primary-100',
    communication: 'bg-green-100',
    analytics: 'bg-blue-100',
    integrations: 'bg-purple-100',
    automation: 'bg-yellow-100',
    branding: 'bg-pink-100',
    email: 'bg-indigo-100',
  };
  return colors[category] || 'bg-primary-100';
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Funcionalidades da Organização</h2>
        <p class="text-gray-600 mt-1">Gerencie quais funcionalidades estão ativas para esta organização</p>
      </div>
      <div class="flex items-center space-x-2">
        <span class="px-3 py-1 text-sm font-medium rounded-full" :class="planColors[currentPlan] || 'bg-gray-100 text-gray-700'">
          Plano: {{ planLabels[currentPlan] || currentPlan }}
        </span>
      </div>
    </div>

    <div v-if="error" class="px-4 py-2 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center justify-between">
      <span>{{ error }}</span>
      <button @click="clearError" class="ml-2 text-red-500 hover:text-red-700">✕</button>
    </div>

    <!-- Plan Upgrade Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-blue-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        <div class="text-sm text-blue-800">
          <p class="font-medium">Funcionalidades por plano</p>
          <p>Algumas funcionalidades requerem planos superiores. <a href="#" class="underline hover:text-blue-700 font-medium">Ver planos</a> ou entre em contato para upgrade.</p>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-gray-600">Ativas</p>
            <p class="text-2xl font-bold text-gray-900">{{ Object.values(featuresByCategory).flat().filter(f => f.enabled).length }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="p-2 bg-gray-100 rounded-lg">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-gray-600">Inativas</p>
            <p class="text-2xl font-bold text-gray-900">{{ Object.values(featuresByCategory).flat().filter(f => !f.enabled).length }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-gray-600">Com Dependências</p>
            <p class="text-2xl font-bold text-gray-900">{{ Object.values(featuresByCategory).flat().filter(f => f.dependencies?.length).length }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-gray-600">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ Object.values(featuresByCategory).flat().length }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Features by Category -->
    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="space-y-8">
      <template v-for="category in categoryOrder" :key="category">
        <div v-if="featuresByCategory[category]?.length" class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
            <div :class="['w-5 h-5 rounded-lg flex items-center justify-center', getCategoryBg(category)]">
              <component :is="getIconComponent(categoryIcons[category])" :class="['w-4 h-4', getCategoryColor(category)]" />
            </div>
            <span>{{ categoryLabels[category] || category }}</span>
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="feature in featuresByCategory[category]"
              :key="feature.key"
              class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow relative group"
              :class="{ 'opacity-50 cursor-not-allowed': !feature.enabled && !canEnableFeature(feature) }"
            >
              <div class="absolute top-3 right-3">
                <span :class="['px-2 py-0.5 text-xs font-medium rounded-full', feature.planColor]">
                  {{ feature.planBadge }}
                </span>
              </div>

              <div :class="['w-12 h-12 rounded-lg flex items-center justify-center mb-4', getCategoryBg(feature.category)]">
                <component :is="getIconComponent(categoryIcons[feature.category])" :class="['w-6 h-6', getCategoryColor(feature.category)]" />
              </div>

              <h4 class="text-lg font-semibold text-gray-900 mb-1">{{ feature.name }}</h4>
              <p class="text-gray-600 text-sm mb-4">{{ feature.description }}</p>

              <!-- Dependencies -->
              <div v-if="feature.dependencies?.length > 0" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center text-xs text-blue-800 mb-1">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                  <span v-if="!getUpgradeTooltip(feature)">Requer: {{ feature.dependencies.map(key => {
                    const dep = Object.values(featuresByCategory).flat().find(f => f.key === key);
                    return dep?.name || key;
                  }).join(', ') }}</span>
                  <span v-else class="text-yellow-800">{{ getUpgradeTooltip(feature) }}</span>
                </div>
              </div>

              <!-- Dependents -->
              <div v-if="feature.dependentFeatures.length > 0" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center text-xs text-yellow-800 mb-1">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  Habilita: {{ feature.dependentFeatures.map(f => f.name).join(', ') }}
                </div>
              </div>

              <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <div class="flex items-center">
                  <span class="text-sm text-gray-500 mr-3">{{ feature.enabled ? 'Ativa' : 'Inativa' }}</span>
                  <button
                    :aria-label="feature.enabled ? 'Desativar' : 'Ativar'"
                    :disabled="isToggling(feature.key) || (!feature.enabled && !canEnableFeature(feature))"
                    @click="handleToggle(feature)"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    :class="feature.enabled ? 'bg-primary-600' : 'bg-gray-200'"
                    role="switch"
                    :aria-checked="feature.enabled"
                  >
                    <span
                      :class="feature.enabled ? 'translate-x-5' : 'translate-x-0'"
                      class="inline-block h-4 w-4 transform bg-white rounded-full shadow ring-0 transition duration-200 ease-in-out"
                    />
                  </button>
                </div>

                <div v-if="isToggling(feature.key)" class="flex items-center text-sm text-gray-500">
                  <svg class="animate-spin h-4 w-4 mr-1 text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  Salvando...
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <div v-if="!loading && Object.values(featuresByCategory).flat().length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma funcionalidade encontrada</h3>
      <p class="mt-1 text-sm text-gray-500">Carregue as funcionalidades disponíveis</p>
    </div>
  </div>
</template>