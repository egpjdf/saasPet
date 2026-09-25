<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { useWorkspaceLimits } from '@/Composables/useWorkspaceLimits';
import type { WorkspaceLimit } from '@/Types/workspaceSettings';

const {
  limits,
  limitsWithValidation,
  hasPendingChanges,
  loading,
  error,
  updating,
  load,
  update,
  reset,
  setPendingValue,
  getPendingValue,
  isUpdating,
  clearError,
} = useWorkspaceLimits();

const editingLimit = ref<string | null>(null);

onMounted(() => {
  load();
});

function handleInputChange(limit: WorkspaceLimit, value: number | null) {
  setPendingValue(limit.key, value);
}

function handleSave(limit: WorkspaceLimit) {
  const newValue = getPendingValue(limit.key);
  if (newValue !== null && newValue !== limit.current_override) {
    update({ limit_key: limit.key, new_value: newValue });
  }
  editingLimit.value = null;
}

function handleReset(limit: WorkspaceLimit) {
  if (limit.current_override !== null) {
    reset(limit.key);
  }
  editingLimit.value = null;
}

function startEditing(limitKey: string) {
  editingLimit.value = limitKey;
}

function cancelEditing() {
  editingLimit.value = null;
}

function formatNumber(value: number | null): string {
  if (value === null) return '—';
  if (value >= 1000000) return (value / 1000000).toFixed(1) + 'M';
  if (value >= 1000) return (value / 1000).toFixed(1) + 'K';
  return value.toLocaleString();
}

function getStatusBadge(limit: WorkspaceLimit): { label: string; class: string } {
  if (limit.is_unlimited) return { label: 'Ilimitado', class: 'bg-gray-100 text-gray-700' };
  if (limit.current_override !== null && limit.current_override > limit.base_plan_limit) {
    return { label: 'Override', class: 'bg-purple-100 text-purple-700' };
  }
  if (limit.current_override !== null && limit.current_override < limit.base_plan_limit) {
    return { label: 'Restrito', class: 'bg-red-100 text-red-700' };
  }
  return { label: 'Plano Base', class: 'bg-green-100 text-green-700' };
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Limites do Workspace</h2>
        <p class="text-gray-600 mt-1">Configure limites personalizados acima do plano base</p>
      </div>
      <div v-if="error" class="px-4 py-2 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center space-x-2">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span>{{ error }}</span>
        <button @click="clearError" class="ml-2 text-red-500 hover:text-red-700">✕</button>
      </div>
    </div>

    <!-- Info Banner -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-start">
        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        <div class="ml-3 text-sm text-blue-800">
          <p class="font-medium">Como funcionam os limites</p>
          <p class="mt-1">Valores em <span class="font-mono text-primary-600">verde</span> são do plano base. Você pode definir <span class="font-mono text-purple-600">overrides</span> maiores que o plano base. Valores menores que o plano base não são permitidos.</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recurso</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plano Base</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Override Atual</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Novo Valor</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="limit in limitsWithValidation" :key="limit.key" :class="editingLimit === limit.key ? 'bg-blue-50' : ''">
            <td class="px-6 py-4">
              <div class="flex flex-col">
                <div class="text-sm font-medium text-gray-900">{{ limit.name }}</div>
                <div class="text-xs text-gray-500">{{ limit.description }}</div>
                <span 
                  v-if="limit.is_unlimited" 
                  class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Ilimitado
                </span>
              </div>
            </td>
            
            <td class="px-6 py-4">
              <div class="text-sm font-mono text-green-700">
                {{ formatNumber(limit.base_plan_limit) }} {{ limit.unit }}
              </div>
            </td>

            <td class="px-6 py-4">
              <div v-if="limit.current_override !== null" class="flex items-center space-x-2">
                <span class="text-sm font-mono text-purple-700">
                  {{ formatNumber(limit.current_override) }} {{ limit.unit }}
                </span>
                <span :class="getStatusBadge(limit).class + ' px-2 py-0.5 rounded-full text-xs font-medium'">
                  {{ getStatusBadge(limit).label }}
                </span>
              </div>
              <div v-else class="text-sm text-gray-500">Usando plano base</div>
            </td>

            <td class="px-6 py-4">
              <div v-if="editingLimit === limit.key" class="flex items-center space-x-2">
                <input
                  type="number"
                  :value="getPendingValue(limit.key) ?? ''"
                  @input="handleInputChange(limit, $event.target.value ? parseInt($event.target.value) : null)"
                  @blur="handleSave(limit)"
                  @keydown.enter="handleSave(limit)"
                  @keydown.escape="cancelEditing"
                  :min="limit.base_plan_limit"
                  :max="limit.is_unlimited ? 999999999 : limit.base_plan_limit * 10"
                  :disabled="isUpdating(limit.key)"
                  class="w-28 px-2 py-1 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  :class="{ 'border-red-500': getPendingValue(limit.key) !== null && getPendingValue(limit.key)! < limit.base_plan_limit }"
                />
                <span class="text-xs text-gray-500">min: {{ limit.base_plan_limit }}</span>
                <div v-if="getPendingValue(limit.key) !== null && getPendingValue(limit.key)! < limit.base_plan_limit" class="text-xs text-red-600">
                  Valor mínimo: {{ limit.base_plan_limit }}
                </div>
              </div>
              <div v-else class="flex items-center space-x-2">
                <button
                  @click="startEditing(limit.key)"
                  :disabled="isUpdating(limit.key) || limit.is_unlimited"
                  class="px-3 py-1 text-xs font-medium text-primary-600 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 disabled:opacity-50"
                >
                  {{ limit.current_override !== null ? 'Alterar' : 'Definir' }}
                </button>
                <button
                  v-if="limit.current_override !== null"
                  @click="handleReset(limit)"
                  :disabled="isUpdating(limit.key)"
                  class="px-3 py-1 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 disabled:opacity-50"
                >
                  Resetar
                </button>
              </div>
            </td>

            <td class="px-6 py-4">
              <div v-if="editingLimit === limit.key" class="flex space-x-2">
                <button
                  @click="handleSave(limit)"
                  :disabled="isUpdating(limit.key) || getPendingValue(limit.key) === null || getPendingValue(limit.key)! < limit.base_plan_limit"
                  class="px-3 py-1 text-xs font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                >
                  <svg v-if="isUpdating(limit.key)" class="animate-spin h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  Salvar
                </button>
                <button
                  @click="cancelEditing"
                  :disabled="isUpdating(limit.key)"
                  class="px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
                >
                  Cancelar
                </button>
              </div>
              <div v-else class="text-sm text-gray-500">—</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading && limits.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum limite configurado</h3>
      <p class="mt-1 text-sm text-gray-500">Os limites serão carregados automaticamente baseados no plano</p>
    </div>
  </div>
</template>