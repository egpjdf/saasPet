<script setup lang="ts">
import { onMounted, ref, computed, h } from 'vue';
import { useWorkspaceIntegrations } from '@/Composables/useWorkspaceIntegrations';
import WhatsAppIntegrationModal from '@/Components/Workspace/Settings/WhatsAppIntegrationModal.vue';
import CrmIntegrationModal from '@/Components/Workspace/Settings/CrmIntegrationModal.vue';
import ErpIntegrationModal from '@/Components/Workspace/Settings/ErpIntegrationModal.vue';
import type { WorkspaceIntegration, IntegrationType, IntegrationTestResponse } from '@/Types/workspaceSettings';

const {
  integrations,
  integrationTypes,
  connectedIntegrations,
  disconnectedIntegrations,
  errorIntegrations,
  loading,
  error,
  testing,
  creating,
  updating,
  deleting,
  load,
  loadTypes,
  create,
  update,
  remove,
  test,
  testConfig,
  isTesting,
  isCreating,
  isUpdating,
  isDeleting,
  clearError,
} = useWorkspaceIntegrations();

const showModal = ref(false);
const editingIntegration = ref<WorkspaceIntegration | null>(null);
const selectedType = ref<IntegrationType | null>(null);
const testResult = ref<IntegrationTestResponse | null>(null);
const showTestResult = ref(false);

onMounted(() => {
  load();
  loadTypes();
});

function openCreateModal(type: IntegrationType) {
  selectedType.value = type;
  editingIntegration.value = null;
  showModal.value = true;
}

function openEditModal(integration: WorkspaceIntegration) {
  editingIntegration.value = integration;
  selectedType.value = integration.type;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingIntegration.value = null;
  selectedType.value = null;
}

async function handleSave(config: any) {
  try {
    if (editingIntegration.value) {
      await update(editingIntegration.value.id, { config });
    } else if (selectedType.value) {
      await create({
        type: selectedType.value,
        name: getIntegrationName(selectedType.value),
        config,
      });
    }
    closeModal();
  } catch (err) {
    // Error handled by composable
  }
}

async function handleTest(config: any) {
  try {
    let result: IntegrationTestResponse;
    if (editingIntegration.value) {
      result = await test(editingIntegration.value.id, { config });
    } else if (selectedType.value) {
      result = await testConfig(selectedType.value, { config });
    } else {
      return;
    }
    testResult.value = result;
    showTestResult.value = true;
  } catch (err) {
    testResult.value = { success: false, message: 'Erro ao testar conexão' };
    showTestResult.value = true;
  }
}

async function handleTestExisting(integration: WorkspaceIntegration) {
  try {
    const result = await test(integration.id, { config: integration.config });
    testResult.value = result;
    showTestResult.value = true;
  } catch (err) {
    testResult.value = { success: false, message: 'Erro ao testar conexão' };
    showTestResult.value = true;
  }
}

async function handleDelete(integration: WorkspaceIntegration) {
  if (confirm(`Tem certeza que deseja remover a integração "${integration.name}"?`)) {
    await remove(integration.id);
  }
}

function getIntegrationName(type: IntegrationType): string {
  const names: Record<IntegrationType, string> = {
    whatsapp: 'WhatsApp Business',
    crm: 'CRM',
    erp: 'ERP',
  };
  return names[type];
}

function getStatusColor(status: string): string {
  const colors: Record<string, string> = {
    connected: 'bg-green-100 text-green-700',
    disconnected: 'bg-gray-100 text-gray-700',
    error: 'bg-red-100 text-red-700',
  };
  return colors[status] || 'bg-gray-100 text-gray-700';
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    connected: 'Conectado',
    disconnected: 'Desconectado',
    error: 'Erro',
  };
  return labels[status] || status;
}

// IntegrationCard component
const IntegrationCard = {
  props: {
    integration: { type: Object as () => WorkspaceIntegration, required: true },
    isTesting: { type: Boolean, required: true },
    isUpdating: { type: Boolean, required: true },
    isDeleting: { type: Boolean, required: true },
  },
  emits: ['edit', 'test', 'delete'],
  setup(props: any, { emit }: any) {
    const statusColors: Record<string, string> = {
      connected: 'bg-green-100 text-green-700',
      disconnected: 'bg-gray-100 text-gray-700',
      error: 'bg-red-100 text-red-700',
    };

    const statusLabels: Record<string, string> = {
      connected: 'Conectado',
      disconnected: 'Desconectado',
      error: 'Erro',
    };

    const typeIcons: Record<string, string> = {
      whatsapp: 'chat-bubble-left-right',
      crm: 'users',
      erp: 'cube-transparent',
    };

    const typeColors: Record<string, string> = {
      whatsapp: 'bg-green-100 text-green-700',
      crm: 'bg-blue-100 text-blue-700',
      erp: 'bg-purple-100 text-purple-700',
    };

    function getTypeIconComponent(type: string) {
      return {
        render() {
          const icons: Record<string, any> = {
            whatsapp: () => h('svg', { class: 'w-4 h-4', fill: 'currentColor', viewBox: '0 0 24 24' }, [
              h('path', { d: 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.48-1.653-1.653-.173-.172-.227-.387-.077-.644.198-.173.865-.744 1.164-.94.32-.198.276-.396.15-.67-.099-.273-.818-2.034-.967-2.33-.148-.296-.054-.408.197-.547.66-.366 3.098-1.044 3.497-1.093.399-.049.597-.049.995-.049.397 0 .597.049.995.049.399.049 2.837.727 3.497 1.093.25.14.344.252.198.547-.15.297-.868 2.058-.967 2.352zm-3.525 3.82c-1.142-.956-2.332-1.648-2.998-1.937-.145-.064-.238-.148-.34-.334l-.188-.333c-.229-.406-.212-.846.1-.99.242-.112.577-.145.973-.039.827.22 1.688.783 2.306 1.57.284.361.435.832.431 1.198-.003.324-.105.706-.375 1.043l-.475.59c-.266.33-.717.878-.992 1.254-.275.376-.557.74-.777 1.057-.256.359-.78 1.11-.524 1.125.023.003.23.01.455.012.225 0 .434-.007.637-.02.402-.024.962-.31 1.534-.87.559-.548.947-1.273.947-2.013 0-.753-.422-1.478-1.044-2.05-.777-.712-1.914-1.393-3.276-1.938zM12 18c-3.315 0-6-2.685-6-6s2.685-6 6-6 6 2.685 6 6-2.685 6-6 6zm0-2c2.21 0 4-1.79 4-4S14.21 6 12 6 8 7.79 8 10s1.79 4 4 4z' })
            ]),
            crm: () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
              h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
            ]),
            erp: () => h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
              h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' })
            ]),
          };
          return icons[type] || icons.whatsapp;
        }
      };
    }

    return { statusColors, statusLabels, typeColors, typeIcons, getTypeIconComponent, h };
  },
  template: `
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div :class="['p-2 rounded-lg', typeColors[props.integration.type] || 'bg-gray-100 text-gray-700']">
            <component :is="getTypeIconComponent(props.integration.type)" />
          </div>
          <div>
            <h4 class="font-medium text-gray-900">{{ props.integration.name }}</h4>
            <p class="text-sm text-gray-500 capitalize">{{ props.integration.type }}</p>
          </div>
        </div>
        <div class="flex items-center space-x-2">
          <span :class="['px-2 py-1 rounded-full text-xs font-medium', statusColors[props.integration.status]]">
            {{ statusLabels[props.integration.status] }}
          </span>
          <button
            @click="$emit('edit', props.integration)"
            :disabled="props.isUpdating || props.isDeleting"
            class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg disabled:opacity-50"
            title="Editar"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </button>
          <button
            @click="$emit('test', props.integration)"
            :disabled="props.isTesting || props.isUpdating || props.isDeleting"
            class="p-2 text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg disabled:opacity-50"
            title="Testar Conexão"
          >
            <svg v-if="!props.isTesting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
          </button>
          <button
            @click="$emit('delete', props.integration)"
            :disabled="props.isDeleting || props.isUpdating"
            class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg disabled:opacity-50"
            title="Remover"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>

      <div class="p-4 bg-gray-50 border-t border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-gray-500">Última sincronização</p>
            <p class="font-medium text-gray-900">
              {{ props.integration.last_sync_at ? new Date(props.integration.last_sync_at).toLocaleString('pt-BR') : 'Nunca' }}
            </p>
          </div>
          <div v-if="props.integration.error_message" class="md:col-span-2">
            <p class="text-gray-500">Último erro</p>
            <p class="font-medium text-red-600">{{ props.integration.error_message }}</p>
          </div>
          <div v-else>
            <p class="text-gray-500">Status</p>
            <p class="font-medium" :class="props.integration.status === 'connected' ? 'text-green-600' : 'text-gray-900'">
              {{ statusLabels[props.integration.status] }}
            </p>
          </div>
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
        <h2 class="text-xl font-semibold text-gray-900">Integrações</h2>
        <p class="text-gray-600 mt-1">Gerencie integrações com serviços externos</p>
      </div>
      <div class="flex space-x-2">
        <button
          @click="openCreateModal('whatsapp')"
          class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 flex items-center space-x-2"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.48-1.653-1.653-.173-.172-.227-.387-.077-.644.198-.173.865-.744 1.164-.94.32-.198.276-.396.15-.67-.099-.273-.818-2.034-.967-2.33-.148-.296-.054-.408.197-.547.66-.366 3.098-1.044 3.497-1.093.399-.049.597-.049.995-.049.397 0 .597.049.995.049.399.049 2.837.727 3.497 1.093.25.14.344.252.198.547-.15.297-.868 2.058-.967 2.352zm-3.525 3.82c-1.142-.956-2.332-1.648-2.998-1.937-.145-.064-.238-.148-.34-.334l-.188-.333c-.229-.406-.212-.846.1-.99.242-.112.577-.145.973-.039.827.22 1.688.783 2.306 1.57.284.361.435.832.431 1.198-.003.324-.105.706-.375 1.043l-.475.59c-.266.33-.717.878-.992 1.254-.275.376-.557.74-.777 1.057-.256.359-.78 1.11-.524 1.125.023.003.23.01.455.012.225 0 .434-.007.637-.02.402-.024.962-.31 1.534-.87.559-.548.947-1.273.947-2.013 0-.753-.422-1.478-1.044-2.05-.777-.712-1.914-1.393-3.276-1.938zM12 18c-3.315 0-6-2.685-6-6s2.685-6 6-6 6 2.685 6 6-2.685 6-6 6zm0-2c2.21 0 4-1.79 4-4S14.21 6 12 6 8 7.79 8 10s1.79 4 4 4z"/>
          </svg>
          <span>WhatsApp</span>
        </button>
        <button
          @click="openCreateModal('crm')"
          class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 flex items-center space-x-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <span>CRM</span>
        </button>
        <button
          @click="openCreateModal('erp')"
          class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 flex items-center space-x-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          <span>ERP</span>
        </button>
      </div>
    </div>

    <div v-if="error" class="px-4 py-2 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center space-x-2">
      <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
      </svg>
      <span>{{ error }}</span>
      <button @click="clearError" class="ml-2 text-red-500 hover:text-red-700">✕</button>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="integrations.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma integração configurada</h3>
      <p class="mt-1 text-sm text-gray-500">Clique em um dos botões acima para adicionar uma nova integração</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Connected Integrations -->
      <div v-if="connectedIntegrations.length > 0" class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span>Conectadas ({{ connectedIntegrations.length }})</span>
        </h3>
        <IntegrationCard 
          v-for="integration in connectedIntegrations" 
          :key="integration.id"
          :integration="integration"
          :is-testing="isTesting(integration.id)"
          :is-updating="isUpdating(integration.id)"
          :is-deleting="isDeleting(integration.id)"
          @edit="openEditModal"
          @test="handleTestExisting"
          @delete="handleDelete"
        />
      </div>

      <!-- Disconnected Integrations -->
      <div v-if="disconnectedIntegrations.length > 0" class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
          <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          <span>Desconectadas ({{ disconnectedIntegrations.length }})</span>
        </h3>
        <IntegrationCard 
          v-for="integration in disconnectedIntegrations" 
          :key="integration.id"
          :integration="integration"
          :is-testing="isTesting(integration.id)"
          :is-updating="isUpdating(integration.id)"
          :is-deleting="isDeleting(integration.id)"
          @edit="openEditModal"
          @test="handleTestExisting"
          @delete="handleDelete"
        />
      </div>

      <!-- Error Integrations -->
      <div v-if="errorIntegrations.length > 0" class="space-y-4">
        <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
          <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>Com Erro ({{ errorIntegrations.length }})</span>
        </h3>
        <IntegrationCard 
          v-for="integration in errorIntegrations" 
          :key="integration.id"
          :integration="integration"
          :is-testing="isTesting(integration.id)"
          :is-updating="isUpdating(integration.id)"
          :is-deleting="isDeleting(integration.id)"
          @edit="openEditModal"
          @test="handleTestExisting"
          @delete="handleDelete"
        />
      </div>
    </div>

    <!-- Test Result Modal -->
    <Transition name="modal-fade">
      <div v-if="showTestResult" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="showTestResult = false" />
          <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Resultado do Teste</h3>
              <button @click="showTestResult = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="p-6">
              <div class="flex items-center space-x-3 mb-4">
                <div :class="testResult?.success ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'" class="p-3 rounded-lg">
                  <svg v-if="testResult?.success" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </div>
                <div>
                  <p class="font-medium">{{ testResult?.success ? 'Conexão bem-sucedida' : 'Falha na conexão' }}</p>
                  <p class="text-sm text-gray-500">{{ testResult?.message }}</p>
                </div>
              </div>
              <div v-if="testResult?.details" class="bg-gray-50 rounded-lg p-4 mb-4 max-h-64 overflow-auto">
                <pre class="text-xs text-gray-700">{{ JSON.stringify(testResult.details, null, 2) }}</pre>
              </div>
              <button
                @click="showTestResult = false"
                class="w-full px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
              >
                Fechar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Integration Modal -->
    <WhatsAppIntegrationModal
      v-if="selectedType === 'whatsapp'"
      :model-value="showModal"
      @update:model-value="closeModal"
      :integration-id="editingIntegration?.id"
      :initial-config="editingIntegration?.config"
      :is-editing="!!editingIntegration"
      :is-testing="isTesting(editingIntegration?.id || '')"
      @save="handleSave"
      @test="handleTest"
    />
    <CrmIntegrationModal
      v-else-if="selectedType === 'crm'"
      :model-value="showModal"
      @update:model-value="closeModal"
      :integration-id="editingIntegration?.id"
      :initial-config="editingIntegration?.config"
      :is-editing="!!editingIntegration"
      :is-testing="isTesting(editingIntegration?.id || '')"
      @save="handleSave"
      @test="handleTest"
    />
    <ErpIntegrationModal
      v-else-if="selectedType === 'erp'"
      :model-value="showModal"
      @update:model-value="closeModal"
      :integration-id="editingIntegration?.id"
      :initial-config="editingIntegration?.config"
      :is-editing="!!editingIntegration"
      :is-testing="isTesting(editingIntegration?.id || '')"
      @save="handleSave"
      @test="handleTest"
    />
  </div>
</template>