<script setup lang="ts">
import { ref, watch } from 'vue';
import type { WhatsAppIntegrationConfig } from '@/Types/workspaceSettings';

interface Props {
  modelValue: boolean;
  integrationId?: string;
  initialConfig?: Partial<WhatsAppIntegrationConfig>;
  isEditing?: boolean;
  isTesting?: boolean;
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
  (e: 'save', config: WhatsAppIntegrationConfig): void;
  (e: 'test', config: WhatsAppIntegrationConfig): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const showToken = ref(false);
const form = ref<WhatsAppIntegrationConfig>({
  phone_number_id: '',
  access_token: '',
  webhook_url: '',
  verify_token: '',
});

const errors = ref<Partial<Record<keyof WhatsAppIntegrationConfig, string>>>({});

watch(() => props.modelValue, (open) => {
  if (open) {
    resetForm();
    if (props.initialConfig) {
      form.value = { ...form.value, ...props.initialConfig } as WhatsAppIntegrationConfig;
    }
  }
});

function resetForm() {
  form.value = {
    phone_number_id: '',
    access_token: '',
    webhook_url: '',
    verify_token: '',
  };
  errors.value = {};
}

function validate(): boolean {
  errors.value = {};
  let valid = true;

  if (!form.value.phone_number_id.trim()) {
    errors.value.phone_number_id = 'Phone Number ID é obrigatório';
    valid = false;
  }

  if (!form.value.access_token.trim()) {
    errors.value.access_token = 'Access Token é obrigatório';
    valid = false;
  }

  if (!form.value.webhook_url.trim()) {
    errors.value.webhook_url = 'Webhook URL é obrigatório';
    valid = false;
  } else if (!form.value.webhook_url.startsWith('https://')) {
    errors.value.webhook_url = 'Webhook URL deve usar HTTPS';
    valid = false;
  }

  if (!form.value.verify_token.trim()) {
    errors.value.verify_token = 'Verify Token é obrigatório';
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
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <div class="fixed inset-0 bg-gray-900/50 transition-opacity" @click="handleClose" />
          <div class="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">
                {{ isEditing ? 'Editar Integração WhatsApp' : 'Nova Integração WhatsApp' }}
              </h3>
              <button @click="handleClose" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <form @submit.prevent="handleSave" class="p-6 space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number ID</label>
                <input
                  type="text"
                  v-model="form.phone_number_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  :class="{ 'border-red-500': errors.phone_number_id }"
                  placeholder="Ex: 123456789012345"
                />
                <p v-if="errors.phone_number_id" class="mt-1 text-sm text-red-600">{{ errors.phone_number_id }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                <div class="relative">
                  <input
                    :type="showToken ? 'text' : 'password'"
                    v-model="form.access_token"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 pr-10"
                    :class="{ 'border-red-500': errors.access_token }"
                    placeholder="EAA..."
                  />
                  <button
                    type="button"
                    @click="showToken = !showToken"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <svg v-if="showToken" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </div>
                <p v-if="errors.access_token" class="mt-1 text-sm text-red-600">{{ errors.access_token }}</p>
                <p class="mt-1 text-xs text-gray-500">Nunca exibimos o token descriptografado após salvar</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Webhook URL</label>
                <input
                  type="url"
                  v-model="form.webhook_url"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  :class="{ 'border-red-500': errors.webhook_url }"
                  placeholder="https://seu-dominio.com/webhook/whatsapp"
                />
                <p v-if="errors.webhook_url" class="mt-1 text-sm text-red-600">{{ errors.webhook_url }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Verify Token</label>
                <input
                  type="text"
                  v-model="form.verify_token"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  :class="{ 'border-red-500': errors.verify_token }"
                  placeholder="Token de verificação do webhook"
                />
                <p v-if="errors.verify_token" class="mt-1 text-sm text-red-600">{{ errors.verify_token }}</p>
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
                  {{ isTesting ? 'Testando...' : 'Testar Conexão' }}
                </button>
                <button
                  type="submit"
                  :disabled="isTesting"
                  class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                >
                  {{ isEditing ? 'Salvar Alterações' : 'Criar Integração' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>