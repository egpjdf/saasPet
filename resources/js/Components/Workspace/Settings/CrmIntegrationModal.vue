<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import type { CrmIntegrationConfig } from '@/Types/workspaceSettings';

interface Props {
  modelValue: boolean;
  integrationId?: string;
  initialConfig?: Partial<CrmIntegrationConfig>;
  isEditing?: boolean;
  isTesting?: boolean;
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
  (e: 'save', config: CrmIntegrationConfig): void;
  (e: 'test', config: CrmIntegrationConfig): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const activeTab = ref<'credentials' | 'mapping' | 'sync'>('credentials');
const showCredentials = ref<Record<string, boolean>>({});

const providers = [
  { value: 'hubspot', label: 'HubSpot', icon: 'hubspot' },
  { value: 'pipedrive', label: 'Pipedrive', icon: 'pipedrive' },
  { value: 'salesforce', label: 'Salesforce', icon: 'salesforce' },
] as const;

const form = ref<CrmIntegrationConfig>({
  provider: 'hubspot',
  auth_type: 'oauth',
  credentials: {},
  field_mapping: {},
  sync_settings: {
    sync_contacts: true,
    sync_deals: true,
    sync_activities: true,
    sync_frequency: 'realtime',
  },
});

const errors = ref<Record<string, string>>({});

const credentialFields = computed(() => {
  const provider = form.value.provider;
  const authType = form.value.auth_type;
  
  if (provider === 'hubspot') {
    return authType === 'oauth' 
      ? ['client_id', 'client_secret', 'redirect_uri']
      : ['api_key'];
  }
  
  if (provider === 'pipedrive') {
    return ['api_token', 'company_domain'];
  }
  
  if (provider === 'salesforce') {
    return authType === 'oauth'
      ? ['client_id', 'client_secret', 'redirect_uri', 'instance_url']
      : ['username', 'password', 'security_token', 'instance_url'];
  }
  
  return [];
});

watch(() => props.modelValue, (open) => {
  if (open) {
    resetForm();
    if (props.initialConfig) {
      form.value = { ...form.value, ...props.initialConfig } as CrmIntegrationConfig;
      // Ensure nested objects are merged properly
      if (props.initialConfig.credentials) {
        form.value.credentials = { ...form.value.credentials, ...props.initialConfig.credentials };
      }
      if (props.initialConfig.field_mapping) {
        form.value.field_mapping = { ...form.value.field_mapping, ...props.initialConfig.field_mapping };
      }
      if (props.initialConfig.sync_settings) {
        form.value.sync_settings = { ...form.value.sync_settings, ...props.initialConfig.sync_settings };
      }
    }
  }
});

function resetForm() {
  form.value = {
    provider: 'hubspot',
    auth_type: 'oauth',
    credentials: {},
    field_mapping: {},
    sync_settings: {
      sync_contacts: true,
      sync_deals: true,
      sync_activities: true,
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
    client_id: 'Client ID',
    client_secret: 'Client Secret',
    redirect_uri: 'Redirect URI',
    api_key: 'API Key',
    api_token: 'API Token',
    company_domain: 'Domínio da Empresa',
    username: 'Usuário',
    password: 'Senha',
    security_token: 'Security Token',
    instance_url: 'Instance URL',
  };
  return labels[field] || field;
}

function getFieldType(field: string): string {
  const secretFields = ['client_secret', 'api_key', 'api_token', 'password', 'security_token'];
  return secretFields.includes(field) ? 'password' : 'text';
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="handleClose" />
          <div class="relative w-full max-w-3xl transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ isEditing ? 'Editar Integração CRM' : 'Nova Integração CRM' }}
              </h3>
              <button @click="handleClose" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Provider & Auth Type Selector -->
            <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Provedor</label>
                  <select
                    v-model="form.provider"
                    @change="form.credentials = {}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  >
                    <option v-for="p in providers" :key="p.value" :value="p.value">{{ p.label }}</option>
                  </select>
                </div>
                <div v-if="form.provider === 'hubspot' || form.provider === 'salesforce'">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Autenticação</label>
                  <select
                    v-model="form.auth_type"
                    @change="form.credentials = {}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  >
                    <option value="oauth">OAuth 2.0</option>
                    <option value="api_key">API Key</option>
                  </select>
                </div>
              </div>
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
                  @click="activeTab = 'mapping'"
                  :class="activeTab === 'mapping' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                  class="py-4 px-1 border-b-2 font-medium text-sm"
                >
                  Mapeamento de Campos
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
                      v-if="['client_secret', 'api_key', 'api_token', 'password', 'security_token'].includes(field)"
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

              <!-- Field Mapping Tab -->
              <div v-show="activeTab === 'mapping'" class="space-y-4">
                <p class="text-sm text-gray-600">Mapeie os campos do CRM para os campos do Saaspet</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Contato (CRM) → Nome (Saaspet)</label>
                    <input
                      v-model="form.field_mapping.contact_name"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      placeholder="Ex: name, full_name"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email (CRM) → Email (Saaspet)</label>
                    <input
                      v-model="form.field_mapping.contact_email"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      placeholder="Ex: email, email_address"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefone (CRM) → Telefone (Saaspet)</label>
                    <input
                      v-model="form.field_mapping.contact_phone"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      placeholder="Ex: phone, phone_number"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Empresa (CRM) → Empresa (Saaspet)</label>
                    <input
                      v-model="form.field_mapping.company_name"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      placeholder="Ex: company, organization_name"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Negócio/Deal (CRM) → Oportunidade (Saaspet)</label>
                    <input
                      v-model="form.field_mapping.deal_name"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      placeholder="Ex: deal_name, opportunity_name"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valor do Negócio (CRM) → Valor (Saaspet)</label>
                    <input
                      v-model="form.field_mapping.deal_value"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                      placeholder="Ex: amount, value"
                    />
                  </div>
                </div>
              </div>

              <!-- Sync Settings Tab -->
              <div v-show="activeTab === 'sync'" class="space-y-4">
                <div class="space-y-3">
                  <h4 class="font-medium text-gray-900">O que sincronizar</h4>
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.sync_settings.sync_contacts" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Sincronizar Contatos</span>
                  </label>
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.sync_settings.sync_deals" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Sincronizar Negócios/Deals</span>
                  </label>
                  <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.sync_settings.sync_activities" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Sincronizar Atividades</span>
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