<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { useWorkspaceWebhooks } from '@/Composables/useWorkspaceWebhooks';
import type { WorkspaceWebhook, WebhookEvent, WebhookTestResponse } from '@/Types/workspaceSettings';

const {
  webhooks,
  webhookEvents,
  eventsByCategory,
  activeWebhooks,
  inactiveWebhooks,
  failedWebhooks,
  loading,
  error,
  testing,
  creating,
  updating,
  deleting,
  regeneratingSecret,
  load,
  loadEvents,
  create,
  update,
  remove,
  test,
  testConfig,
  regenerateSecret,
  isTesting,
  isCreating,
  isUpdating,
  isDeleting,
  isRegeneratingSecret,
  clearError,
} = useWorkspaceWebhooks();

const showModal = ref(false);
const editingWebhook = ref<WorkspaceWebhook | null>(null);
const testResult = ref<WebhookTestResponse | null>(null);
const showTestResult = ref(false);
const newHmacSecret = ref<string | null>(null);
const showSecret = ref(false);

onMounted(() => {
  load();
  loadEvents();
});

function openCreateModal() {
  editingWebhook.value = null;
  showModal.value = true;
}

function openEditModal(webhook: WorkspaceWebhook) {
  editingWebhook.value = webhook;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingWebhook.value = null;
  newHmacSecret.value = null;
  showSecret.value = false;
}

async function handleSave(data: { url: string; events: string[] }) {
  try {
    if (editingWebhook.value) {
      await update(editingWebhook.value.id, data);
    } else {
      await create(data);
    }
    closeModal();
  } catch (err) {
    // Error handled by composable
  }
}

async function handleTest(webhook: WorkspaceWebhook) {
  try {
    const result = await test(webhook.id);
    testResult.value = result;
    showTestResult.value = true;
  } catch (err) {
    testResult.value = { success: false, status_code: 0, response_body: 'Erro ao testar webhook', response_time_ms: 0 };
    showTestResult.value = true;
  }
}

async function handleTestConfig(data: { url: string; events: string[] }) {
  try {
    const result = await testConfig(data);
    testResult.value = result;
    showTestResult.value = true;
  } catch (err) {
    testResult.value = { success: false, status_code: 0, response_body: 'Erro ao testar configuração', response_time_ms: 0 };
    showTestResult.value = true;
  }
}

async function handleDelete(webhook: WorkspaceWebhook) {
  if (confirm(`Tem certeza que deseja remover o webhook "${webhook.url}"?`)) {
    await remove(webhook.id);
  }
}

async function handleRegenerateSecret(webhook: WorkspaceWebhook) {
  if (confirm('Isso invalidará o segredo atual. Tem certeza?')) {
    try {
      const secret = await regenerateSecret(webhook.id);
      newHmacSecret.value = secret;
      showSecret.value = true;
    } catch (err) {
      // Error handled by composable
    }
  }
}

function copyToClipboard(text: string) {
  navigator.clipboard.writeText(text);
}

function formatDate(dateString: string | null): string {
  if (!dateString) return 'Nunca';
  return new Date(dateString).toLocaleString('pt-BR');
}

function getStatusBadge(status: string): { label: string; class: string } {
  const badges: Record<string, { label: string; class: string }> = {
    active: { label: 'Ativo', class: 'bg-green-100 text-green-700' },
    inactive: { label: 'Inativo', class: 'bg-gray-100 text-gray-700' },
    failed: { label: 'Falhou', class: 'bg-red-100 text-red-700' },
  };
  return badges[status] || { label: status, class: 'bg-gray-100 text-gray-700' };
}

// WebhookModal component
const WebhookModal = {
  props: {
    modelValue: { type: Boolean, required: true },
    webhook: { type: Object as () => WorkspaceWebhook | null, default: null },
    events: { type: Array as () => WebhookEvent[], default: () => [] },
    isTesting: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'save', 'test'],
  setup(props: any, { emit }: any) {
    const form = ref({ url: '', events: [] as string[] });
    const errors = ref<{ url?: string; events?: string }>({});

    const eventsByCategory = computed(() => {
      const result: Record<string, WebhookEvent[]> = {};
      props.events.forEach(event => {
        if (!result[event.category]) result[event.category] = [];
        result[event.category].push(event);
      });
      return result;
    });

    function validate(): boolean {
      errors.value = {};
      let valid = true;

      if (!form.value.url.trim()) {
        errors.value.url = 'URL é obrigatória';
        valid = false;
      } else if (!form.value.url.startsWith('https://')) {
        errors.value.url = 'URL deve usar HTTPS';
        valid = false;
      }

      if (form.value.events.length === 0) {
        errors.value.events = 'Selecione pelo menos um evento';
        valid = false;
      }

      return valid;
    }

    function handleSave() {
      if (validate()) {
        emit('save', { ...form.value });
      }
    }

    function handleTest() {
      if (validate()) {
        emit('test', { ...form.value });
      }
    }

    function handleClose() {
      emit('update:modelValue', false);
    }

    function toggleEvent(eventKey: string) {
      const index = form.value.events.indexOf(eventKey);
      if (index === -1) {
        form.value.events.push(eventKey);
      } else {
        form.value.events.splice(index, 1);
      }
    }

    function selectAllEvents(category: string) {
      const categoryEvents = eventsByCategory.value[category] || [];
      categoryEvents.forEach(event => {
        if (!form.value.events.includes(event.key)) {
          form.value.events.push(event.key);
        }
      });
    }

    function deselectAllEvents(category: string) {
      const categoryEvents = eventsByCategory.value[category] || [];
      form.value.events = form.value.events.filter(e => !categoryEvents.some(ce => ce.key === e));
    }

    function handleUrlInput(event: Event) {
      const target = event.target as HTMLInputElement;
      form.value.url = target.value;
      if (errors.value.url && target.value.startsWith('https://')) {
        errors.value.url = undefined;
      }
    }

    function copyToClipboard(text: string) {
      navigator.clipboard.writeText(text);
    }

    return {
      form,
      errors,
      eventsByCategory,
      validate,
      handleSave,
      handleTest,
      handleClose,
      toggleEvent,
      selectAllEvents,
      deselectAllEvents,
      handleUrlInput,
      copyToClipboard,
    };
  },
  template: `
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="handleClose" />
            <div class="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all max-h-[90vh] flex flex-col">
              <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900">
                  {{ webhook ? 'Editar Webhook' : 'Novo Webhook' }}
                </h3>
                <button @click="handleClose" class="text-gray-400 hover:text-gray-600">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <form @submit.prevent="handleSave" class="flex-1 overflow-y-auto p-6 space-y-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">URL do Endpoint</label>
                  <input
                    type="url"
                    v-model="form.url"
                    @input="handleUrlInput"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono text-sm"
                    :class="{ 'border-red-500': errors.url }"
                    placeholder="https://seu-dominio.com/webhook"
                  />
                  <p v-if="errors.url" class="mt-1 text-sm text-red-600">{{ errors.url }}</p>
                  <p class="mt-1 text-xs text-gray-500">Deve usar HTTPS. Receberá POST com JSON payload.</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Eventos</label>
                  <p class="text-xs text-gray-500 mb-2">{{ form.events.length }} evento(s) selecionado(s)</p>
                  <div v-if="errors.events" class="mb-2 text-sm text-red-600">{{ errors.events }}</div>
                  
                  <template v-for="(events, category) in eventsByCategory" :key="category">
                    <div class="mb-4">
                      <div class="flex items-center justify-between mb-2">
                        <h4 class="font-medium text-gray-900 capitalize">{{ category }}</h4>
                        <div class="flex space-x-2">
                          <button
                            type="button"
                            @click="selectAllEvents(category)"
                            class="text-xs text-primary-600 hover:text-primary-700"
                          >
                            Selecionar todos
                          </button>
                          <button
                            type="button"
                            @click="deselectAllEvents(category)"
                            class="text-xs text-gray-500 hover:text-gray-700"
                          >
                            Limpar
                          </button>
                        </div>
                      </div>
                      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label v-for="event in events" :key="event.key" class="flex items-center space-x-2 cursor-pointer">
                          <input
                            type="checkbox"
                            :value="event.key"
                            :checked="form.events.includes(event.key)"
                            @change="toggleEvent(event.key)"
                            class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                          />
                          <span class="text-sm text-gray-700">{{ event.name }}</span>
                        </label>
                      </div>
                    </div>
                  </template>
                </div>

                <div v-if="webhook?.hmac_secret" class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                  <h4 class="font-medium text-purple-900 mb-2">Segredo HMAC</h4>
                  <p class="text-sm text-purple-700 mb-2">Use este segredo para validar a assinatura dos webhooks recebidos (header X-HMAC-Signature)</p>
                  <div class="flex space-x-2">
                    <input
                      type="text"
                      :value="webhook.hmac_secret"
                      readonly
                      class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-white font-mono text-sm"
                    />
                    <button
                      type="button"
                      @click="copyToClipboard(webhook.hmac_secret!)"
                      class="px-4 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 flex items-center space-x-2"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2h-10a2 2 0 01-2-2v-1M8 5v12M8 5l8 8" />
                      </svg>
                      <span>Copiar</span>
                    </button>
                  </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                  <button
                    type="button"
                    @click="handleClose"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                  >
                    Cancelar
                  </button>
                  <button
                    type="button"
                    @click="handleTest"
                    :disabled="isTesting"
                    class="px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 disabled:opacity-50"
                  >
                    {{ isTesting ? 'Testando...' : 'Testar Entrega' }}
                  </button>
                  <button
                    type="submit"
                    :disabled="isTesting"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                  >
                    {{ webhook ? 'Salvar Alterações' : 'Criar Webhook' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
  `,
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900">Webhooks</h2>
        <p class="text-gray-600 mt-1">Configure endpoints para receber eventos em tempo real</p>
      </div>
      <button
        @click="openCreateModal"
        :disabled="isCreating"
        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center space-x-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Novo Webhook</span>
      </button>
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

    <div v-else-if="webhooks.length === 0" class="text-center py-12 bg-white rounded-lg border border-gray-200">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum webhook configurado</h3>
      <p class="mt-1 text-sm text-gray-500">Clique em "Novo Webhook" para criar seu primeiro endpoint</p>
    </div>

    <div v-else class="space-y-6">
      <!-- Summary Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-gray-600">Ativos</p>
              <p class="text-2xl font-bold text-gray-900">{{ activeWebhooks.length }}</p>
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
              <p class="text-sm text-gray-600">Inativos</p>
              <p class="text-2xl font-bold text-gray-900">{{ inactiveWebhooks.length }}</p>
            </div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <div class="flex items-center">
            <div class="p-2 bg-red-100 rounded-lg">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-gray-600">Com Falha</p>
              <p class="text-2xl font-bold text-gray-900">{{ failedWebhooks.length }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Webhooks Table -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Eventos</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Disparo</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="webhook in webhooks" :key="webhook.id">
              <td class="px-6 py-4">
                <div class="text-sm font-mono text-gray-900 truncate max-w-xs">{{ webhook.url }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="event in webhook.events" 
                    :key="event"
                    class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded"
                  >
                    {{ event }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusBadge(webhook.status).class + ' px-2 py-1 rounded-full text-xs font-medium'">
                  {{ getStatusBadge(webhook.status).label }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-500">{{ formatDate(webhook.last_triggered_at) }}</div>
                <div v-if="webhook.last_response_code" class="text-xs text-gray-400">
                  HTTP {{ webhook.last_response_code }} • {{ webhook.last_response_body?.substring(0, 50) }}{{ webhook.last_response_body && webhook.last_response_body.length > 50 ? '...' : '' }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                  <button
                    @click="handleTest(webhook)"
                    :disabled="isTesting(webhook.id) || isUpdating(webhook.id) || isDeleting(webhook.id)"
                    class="p-2 text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg disabled:opacity-50"
                    title="Testar"
                  >
                    <svg v-if="!isTesting(webhook.id)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                  </button>
                  <button
                    @click="openEditModal(webhook)"
                    :disabled="isUpdating(webhook.id) || isDeleting(webhook.id)"
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg disabled:opacity-50"
                    title="Editar"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    v-if="webhook.hmac_secret"
                    @click="handleRegenerateSecret(webhook)"
                    :disabled="isRegeneratingSecret(webhook.id) || isUpdating(webhook.id) || isDeleting(webhook.id)"
                    class="p-2 text-purple-600 hover:text-purple-700 hover:bg-purple-50 rounded-lg disabled:opacity-50"
                    title="Regenerar Segredo HMAC"
                  >
                    <svg v-if="!isRegeneratingSecret(webhook.id)" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                  </button>
                  <button
                    @click="handleDelete(webhook)"
                    :disabled="isDeleting(webhook.id) || isUpdating(webhook.id)"
                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg disabled:opacity-50"
                    title="Remover"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Available Events Reference -->
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Eventos Disponíveis</h3>
          <p class="text-sm text-gray-500 mt-1">Referência dos eventos que podem ser enviados via webhook</p>
        </div>
        <div class="p-6">
          <template v-for="(events, category) in eventsByCategory" :key="category">
            <h4 class="font-medium text-gray-900 mb-2 capitalize">{{ category }}</h4>
            <div class="flex flex-wrap gap-2 mb-4">
              <span 
                v-for="event in events" 
                :key="event.key"
                class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded font-mono"
              >
                {{ event.key }}
              </span>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Test Result Modal -->
    <Transition name="modal-fade">
      <div v-if="showTestResult" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="showTestResult = false" />
          <div class="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
              <h3 class="text-lg font-semibold text-gray-900">Resultado do Teste</h3>
              <button @click="showTestResult = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
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
                  <p class="font-medium">{{ testResult?.success ? 'Entrega bem-sucedida' : 'Falha na entrega' }}</p>
                  <p class="text-sm text-gray-500">HTTP {{ testResult?.status_code }} • {{ testResult?.response_time_ms }}ms</p>
                </div>
              </div>
              <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-64 overflow-auto">
                <pre class="text-xs text-gray-700">{{ testResult?.response_body }}</pre>
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

    <!-- New HMAC Secret Modal -->
    <Transition name="modal-fade">
      <div v-if="showSecret && newHmacSecret" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="showSecret = false" />
          <div class="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">Novo Segredo HMAC Gerado</h3>
              <button @click="showSecret = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div class="p-6">
              <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <div class="flex items-center text-red-700 mb-2">
                  <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                  <span class="font-medium">Importante: Este segredo não será exibido novamente!</span>
                </div>
                <p class="text-sm text-red-600">Copie e armazene este segredo em local seguro. Ele é usado para validar a assinatura dos webhooks recebidos.</p>
              </div>
              <div class="flex space-x-2 mb-4">
                <input
                  type="text"
                  :value="newHmacSecret"
                  readonly
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 font-mono text-sm"
                />
                <button
                  @click="copyToClipboard(newHmacSecret)"
                  class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 flex items-center space-x-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 012-2h10a2 2 0 012 2v12a2 2 0 01-2 2h-10a2 2 0 01-2-2v-1M8 5v12M8 5l8 8" />
                  </svg>
                  <span>Copiar</span>
                </button>
              </div>
              <button
                @click="showSecret = false"
                class="w-full px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700"
              >
                Entendi, Fechar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Webhook Modal -->
    <WebhookModal
      :model-value="showModal"
      @update:model-value="closeModal"
      :webhook="editingWebhook"
      :events="webhookEvents"
      :is-testing="editingWebhook ? isTesting(editingWebhook.id) : isCreating"
      @save="handleSave"
      @test="handleTestConfig"
    />
  </div>
</template>