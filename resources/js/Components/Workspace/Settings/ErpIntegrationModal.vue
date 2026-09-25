<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import type { ErpIntegrationConfig } from '@/Types/workspaceSettings';

interface Props {
  modelValue: boolean;
  integrationId?: string;
  initialConfig?: Partial<ErpIntegrationConfig>;
  isEditing?: boolean;
  isTesting?: boolean;
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
  (e: 'save', config: ErpIntegrationConfig): void;
  (e: 'test', config: ErpIntegrationConfig): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const activeTab = ref<'credentials' | 'sync'>('credentials');
const showCredentials = ref<Record<string, boolean>>({});

const providers = [
  { value: 'tiny', label: 'Tiny ERP', icon: 'tiny' },
  { value: 'bling', label: 'Bling', icon: 'bling' },
  { value: 'omie', label: 'Omie', icon: 'omie' },
] as const;

const form = ref<ErpIntegrationConfig>({
  provider: 'tiny',
  credentials: {},
  sync_settings: {
    sync_products: true,
    sync_orders: true,
    sync_customers: true,
    sync_inventory: true,
    sync_frequency: 'realtime',
  },
});

const errors = ref<Record<string, string>>({});

const credentialFields = computed(() => {
  const provider = form.value.provider;
  
  if (provider === 'tiny') {
    return ['api_token', 'account_id'];
  }
  
  if (provider === 'bling') {
    return ['api_key'];
  }
  
  if (provider === 'omie') {
    return ['app_key', 'app_secret', 'caller'];
  }
  
  return [];
});

watch(() => props.modelValue, (open) => {
  if (open) {
    resetForm();
    if (props.initialConfig) {
      form.value = { ...form.value, ...props.initialConfig } as ErpIntegrationConfig;
      if (props.initialConfig.credentials) {
        form.value.credentials = { ...form.value.credentials, ...props.initialConfig.credentials };
      }
      if (props.initialConfig.sync_settings) {
        form.value.sync_settings = { ...form.value.sync_settings, ...props.initialConfig.sync_settings };
      }
    }
  }
});

function resetForm() {
  form.value = {
    provider: 'tiny',
    credentials: {},
    sync_settings: {
      sync_products: true,
      sync_orders: true,
      sync_customers: true,
      sync_inventory: true,
      sync_frequency: 'realtime',
    },
  };
  errors.value = {};
  activeTab.value = 'credentials';
}

function validate(): boolean {
  errors.value = {};
  let valid = true;

  credentialFields.value.forEach(field => {
    if (!form.value.credentials[field]?.trim()) {
      errors.value[field] = `${field} é obrigatório`;
      valid = false;
    }
  });

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

function getFieldLabel(field: string): string {
  const labels: Record<string, string> = {
    api_token: 'API Token',
    account_id: 'Account ID',
    api_key: 'API Key',
    app_key: 'App Key',
    app_secret: 'App Secret',
    caller: 'Caller (Identificação da Aplicação)',
  };
  return labels[field] || field;
}

function getFieldType(field: string): string {
  const secretFields = ['api_token', 'api_key', 'app_secret'];
  return secretFields.includes(field) ? 'password' : 'text';
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="handleClose" />
          <div class="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ isEditing ? 'Editar Integração ERP' : 'Nova Integração ERP' }}
              </h3>
              <button @click="handleClose" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Provider Selector -->
            <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
              <label class="block text-sm font-medium text-gray-700 mb-1">Provedor ERP</label>
              <select
                v-model="form.provider"
                @change="form.credentials = {}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option v-for="p in providers" :key="p.value" :value="p.value">{{ p.label }}</option>
              </select>
            </div>

            <!-- Tab Navigation -->
            <div class="px-6 border-b border-gray-200 flex-shrink-0">
              <nav class="flex space-x-8" aria-label="Tabs">
                <button
                  @click="activeTab = 'credentials'"
                  :class="activeTab === 'credentials' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                  class="py-4 px-1 border-b-2 font-medium text-sm"
                >
                  Credenciais
                </button>
                <button
                  @click="activeTab = 'sync'"
                  :class="activeTab === 'sync' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                  class="py-4 px-1 border-b-2 font-medium text-sm"
                >
                  Configurações de Sync
                </button>
              </nav>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
              <!-- Credentials Tab -->
              <div v-show="activeTab === 'credentials'" class="space-y-4">
                <div v-for="field in credentialFields" :key="field" class="relative">
                  <label :for="field" class="block text-sm font-medium text-gray-700 mb-1">{{ getFieldLabel(field) }}</label>
                  <div class="relative">
                    <input
                      :id="field"
                      :type="showCredentials[field] ? 'text' : getFieldType(field)"
                      v-model="form.credentials[field]"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 pr-10"
                      :class="{ 'border-red-500': errors[field] }"
                    />
                    <button
                      v-if="['api_token', 'api_key', 'app_secret'].includes(field)"
                      type="button"
                      @click="showCredentials[field] = !showCredentials[field]"
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                    >
                      <svg v-if="showCredentials[field]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                  </div>
                  <p v-if="errors[field]" class="mt-1 text-sm text-red-600">{{ errors[field] }}</p>
                </div>
                <p class="text-xs text-gray-500 mt-4">Credenciais são criptografadas e nunca exibidas descriptografadas após salvar</p>
              </div>

              <!-- Sync Settings Tab -->
              <div v-show="activeTab === 'sync'" class="space-y-4">
                <div class="space-y-3">
                  <h4 class="font-medium text-gray-900">O que sincronizar</h4>
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.sync_settings.sync_products" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Sincronizar Produtos</span>
                  </label>
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.sync_settings.sync_orders" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Sincronizar Pedidos</span>
                  </label>
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.sync_settings.sync_customers" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Sincronizar Clientes</span>
                  </label>
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.sync_settings.sync_inventory" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Sincronizar Estoque</span>
                  </label>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Frequência de Sincronização</label>
                  <select
                    v-model="form.sync_settings.sync_frequency"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  >
                    <option value="realtime">Tempo Real (Webhook)</option>
                    <option value="hourly">A cada hora</option>
                    <option value="daily">Diariamente</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex-shrink-0">
              <div class="flex justify-end space-x-3">
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
                  {{ isTesting ? 'Testando...' : 'Testar Conexão' }}
                </button>
                <button
                  type="button"
                  @click="handleSave"
                  :disabled="isTesting"
                  class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                >
                  {{ isEditing ? 'Salvar Alterações' : 'Criar Integração' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>