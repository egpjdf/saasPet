<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { useWorkspaceFeatures } from '@/Composables/useWorkspaceFeatures';
import type { WorkspaceFeature } from '@/Types/workspaceSettings';

const {
  features,
  featuresByCategory,
  enabledFeatures,
  disabledFeatures,
  canEnableFeature,
  getDependentFeatures,
  loading,
  error,
  toggling,
  load,
  loadAvailable,
  toggle,
  isToggling,
  clearError,
} = useWorkspaceFeatures();

const categoryLabels: Record<string, string> = {
  core: 'Essenciais',
  communication: 'Comunicação',
  analytics: 'Analytics & Relatórios',
  integrations: 'Integrações',
  automation: 'Automação',
};

const categoryIcons: Record<string, string> = {
  core: 'cube',
  communication: 'chat-bubble-left-right',
  analytics: 'chart-bar',
  integrations: 'puzzle-piece',
  automation: 'bolt',
};

const planLabels: Record<string, string> = {
  free: 'Gratuito',
  starter: 'Starter',
  professional: 'Professional',
  enterprise: 'Enterprise',
};

const planColors: Record<string, string> = {
  free: 'bg-gray-100 text-gray-700',
  starter: 'bg-green-100 text-green-700',
  professional: 'bg-blue-100 text-blue-700',
  enterprise: 'bg-purple-100 text-purple-700',
};

onMounted(() => {
  load();
  loadAvailable();
});

function handleToggle(feature: WorkspaceFeature) {
  if (isToggling(feature.key)) return;
  if (!feature.enabled && !canEnableFeature.value(feature)) return;
  
  toggle({ feature_key: feature.key, enabled: !feature.enabled });
}

function getDependencyTooltip(feature: WorkspaceFeature): string {
  if (!feature.dependencies || feature.dependencies.length === 0) return '';
  const deps = feature.dependencies.map(key => {
    const dep = features.value.find(f => f.key === key);
    return dep?.name || key;
  });
  return `Requer: ${deps.join(', ')}`;
}

// FeatureCard component (inline)
const FeatureCard = {
  props: {
    feature: { type: Object as () => WorkspaceFeature, required: true },
    canEnable: { type: Boolean, required: true },
    dependentFeatures: { type: Array as () => WorkspaceFeature[], required: true },
    toggling: { type: Boolean, required: true },
  },
  emits: ['toggle'],
  setup(props: any, { emit }: any) {
    function handleClick() {
      if (props.toggling) return;
      if (!props.feature.enabled && !props.canEnable) return;
      emit('toggle', props.feature);
    }

    return { handleClick, props, planLabels, planColors };
  },
  template: `
    <div 
      class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition-shadow relative group"
      @click="handleClick"
      :class="{ 'opacity-50 cursor-not-allowed': !props.feature.enabled && !props.canEnable }"
    >
      <div class="absolute top-3 right-3">
        <span 
          :class="['px-2 py-0.5 text-xs font-medium rounded-full', planColors[props.feature.required_plan] || 'bg-gray-100 text-gray-700']"
        >
          {{ planLabels[props.feature.required_plan] || props.feature.required_plan }}
        </span>
      </div>

      <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
      </div>

      <h4 class="text-lg font-semibold text-gray-900 mb-1">{{ props.feature.name }}</h4>
      <p class="text-gray-600 text-sm mb-4">{{ props.feature.description }}</p>

      <div v-if="props.dependentFeatures.length > 0" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex items-center text-xs text-yellow-800 mb-1">
          <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          Esta funcionalidade habilita: {{ props.dependentFeatures.map(f => f.name).join(', ') }}
        </div>
      </div>

      <div v-if="props.feature.dependencies?.length > 0" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-center text-xs text-blue-800 mb-1">
          <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
          Requer: {{ props.feature.dependencies.join(', ') }}
        </div>
      </div>

      <div class="flex items-center justify-between pt-4 border-t border-gray-100">
        <div class="flex items-center">
          <span class="text-sm text-gray-500 mr-3">{{ props.feature.enabled ? 'Ativa' : 'Inativa' }}</span>
          <button
            :aria-label="props.feature.enabled ? 'Desativar' : 'Ativar'"
            :disabled="props.toggling || (!props.feature.enabled && !props.canEnable)"
            @click.stop.prevent="handleClick"
            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
            :class="props.feature.enabled ? 'bg-primary-600' : 'bg-gray-200'"
            role="switch"
            :aria-checked="props.feature.enabled"
          >
            <span
              :class="props.feature.enabled ? 'translate-x-5' : 'translate-x-0'"
              class="inline-block h-4 w-4 transform bg-white rounded-full shadow ring-0 transition duration-200 ease-in-out"
            />
          </button>
        </div>

        <div v-if="props.toggling" class="flex items-center text-sm text-gray-500">
          <svg class="animate-spin h-4 w-4 mr-1 text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          Salvando...
        </div>
      </div>
    </div>
  `,
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Funcionalidades do Workspace</h2>
        <p class="text-gray-600 mt-1">Gerencie quais funcionalidades estão ativas para este workspace</p>
      </div>
      <div v-if="error" class="px-4 py-2 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center space-x-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span>{{ error }}</span>
        <button @click="clearError" class="ml-2 text-red-500 hover:text-red-700">✕</button>
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
            <p class="text-2xl font-bold text-gray-900">{{ enabledFeatures.length }}</p>
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
            <p class="text-2xl font-bold text-gray-900">{{ disabledFeatures.length }}</p>
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
            <p class="text-2xl font-bold text-gray-900">{{ features.filter(f => f.dependencies?.length).length }}</p>
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
            <p class="text-2xl font-bold text-gray-900">{{ features.length }}</p>
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

    <div v-else class="space-y-6">
      <template v-for="category in ['core', 'communication', 'analytics', 'integrations', 'automation']" :key="category">
        <div v-if="featuresByCategory[category]?.length" class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
            <component 
              :is="getIconComponent(categoryIcons[category])" 
              :class="['w-5 h-5', planColors[category] || 'text-primary-600']" 
            />
            <span>{{ categoryLabels[category] }}</span>
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <FeatureCard 
              v-for="feature in featuresByCategory[category]" 
              :key="feature.key"
              :feature="feature"
              :can-enable="canEnableFeature(feature)"
              :dependent-features="getDependentFeatures(feature.key)"
              :toggling="isToggling(feature.key)"
              @toggle="handleToggle"
            />
          </div>
        </div>
      </template>
    </div>

    <div v-if="!loading && features.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma funcionalidade encontrada</h3>
      <p class="mt-1 text-sm text-gray-500">Carregue as funcionalidades disponíveis</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { h } from 'vue';

function getIconComponent(iconName: string) {
  const icons: Record<string, any> = {
    cube: () => import('@heroicons/vue/24/outline/CubeIcon'),
    'chat-bubble-left-right': () => import('@heroicons/vue/24/outline/ChatBubbleLeftRightIcon'),
    'chart-bar': () => import('@heroicons/vue/24/outline/ChartBarIcon'),
    'puzzle-piece': () => import('@heroicons/vue/24/outline/PuzzlePieceIcon'),
    bolt: () => import('@heroicons/vue/24/outline/BoltIcon'),
  };
  return icons[iconName] || icons.cube;
}
</script>