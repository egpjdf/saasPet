<script setup lang="ts">
import { onMounted, computed } from 'vue';
import { useWorkspaceNotifications } from '@/Composables/useWorkspaceNotifications';
import type { NotificationChannel, NotificationCategory } from '@/Types/workspaceSettings';
import { h } from 'vue';

const {
  channels,
  categories,
  matrixMap,
  digestFrequency,
  loading,
  error,
  saving,
  load,
  loadChannels,
  loadCategories,
  updateMatrixDebounced,
  updateDigest,
  getMatrixValue,
  toggleMatrixValue,
  clearError,
} = useWorkspaceNotifications();

const digestOptions = [
  { value: 'immediate', label: 'Imediato', description: 'Receba notificações assim que ocorrerem' },
  { value: 'hourly', label: 'A cada hora', description: 'Agrupe notificações e envie a cada hora' },
  { value: 'daily', label: 'Diário', description: 'Resumo diário enviado pela manhã' },
  { value: 'weekly', label: 'Semanal', description: 'Resumo semanal enviado às segundas-feiras' },
  { value: 'never', label: 'Nunca', description: 'Não enviar digests automáticos' },
] as const;

onMounted(() => {
  load();
});

function handleDigestChange(frequency: typeof digestOptions[0]['value']) {
  updateDigest({ frequency });
}

// ChannelIcon component
const ChannelIcon = {
  props: {
    channel: { type: String, required: true },
  },
  setup(props: any) {
    const icons: Record<string, any> = {
      email: () => import('@heroicons/vue/24/outline/MailIcon'),
      sms: () => import('@heroicons/vue/24/outline/ChatBubbleLeftRightIcon'),
      push: () => import('@heroicons/vue/24/outline/BellIcon'),
      in_app: () => import('@heroicons/vue/24/outline/BellAlertIcon'),
      whatsapp: () => import('@heroicons/vue/24/outline/ChatBubbleBottomCenterTextIcon'),
    };
    const IconComponent = icons[props.channel] || icons.email;
    return () => h(IconComponent, { class: 'w-5 h-5' });
  },
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Preferências de Notificação</h2>
        <p class="text-gray-600 mt-1">Configure como e quando receber notificações</p>
      </div>
      <div v-if="error" class="px-4 py-2 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center space-x-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span>{{ error }}</span>
        <button @click="clearError" class="ml-2 text-red-500 hover:text-red-700">✕</button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="space-y-6">
      <!-- Notification Matrix -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Canais × Categorias</h3>
          <p class="text-sm text-gray-500 mt-1">Ative/desative notificações por canal para cada categoria</p>
        </div>
        
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Canal</th>
                <th v-for="category in categories" :key="category.key" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ category.name }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="channel in channels" :key="channel.key">
                <td class="px-4 py-3">
                  <div class="flex items-center space-x-3">
                    <div :class="['p-2 rounded-lg', channel.enabled ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-600']">
                      <ChannelIcon :channel="channel.key" />
                    </div>
                    <div>
                      <p class="font-medium text-gray-900">{{ channel.name }}</p>
                      <p class="text-xs text-gray-500">{{ channel.description }}</p>
                    </div>
                  </div>
                </td>
                <td v-for="category in categories" :key="category.key" class="px-4 py-3 text-center">
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      :checked="getMatrixValue(channel.key, category.key)"
                      @change="toggleMatrixValue(channel.key, category.key)"
                      :disabled="!channel.enabled || saving"
                      class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                  </label>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
          <p class="text-xs text-gray-500">As alterações são salvas automaticamente após 500ms de inatividade</p>
        </div>
      </div>

      <!-- Digest Frequency -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Frequência de Digest</h3>
          <p class="text-sm text-gray-500 mt-1">Escolha com que frequência receber resumos de notificações</p>
        </div>
        
        <div class="p-6 space-y-3">
          <label 
            v-for="option in digestOptions" 
            :key="option.value"
            class="flex items-center space-x-4 p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
            :class="digestFrequency === option.value ? 'border-primary-300 bg-primary-50' : ''"
          >
            <input
              type="radio"
              :value="option.value"
              v-model="digestFrequency"
              @change="handleDigestChange(option.value)"
              class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
            />
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ option.label }}</p>
              <p class="text-sm text-gray-500">{{ option.description }}</p>
            </div>
            <div v-if="digestFrequency === option.value" class="text-primary-600">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </div>
          </label>
        </div>
      </div>

      <!-- Channel Master Toggles -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Canais Globais</h3>
          <p class="text-sm text-gray-500 mt-1">Desative um canal completamente para todas as categorias</p>
        </div>
        
        <div class="p-6 space-y-4">
          <div v-for="channel in channels" :key="channel.key" class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div :class="['p-2 rounded-lg', channel.enabled ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-600']">
                <ChannelIcon :channel="channel.key" />
              </div>
              <div>
                <p class="font-medium text-gray-900">{{ channel.name }}</p>
                <p class="text-xs text-gray-500">{{ channel.description }}</p>
              </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="channel.enabled"
                @change="updateMatrixDebounced({ channel_key: channel.key, category_key: 'all', enabled: channel.enabled })"
                class="sr-only peer"
              />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>