<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useOrganizationSettings } from '@/Composables/useOrganizationSettings';
import { useNotificationStore } from '@/Stores/notification';
import { testEmail } from '@/Services/api/organizationSettings';

const {
  email,
  loading,
  updateEmailConfig,
  error: settingsError,
  clearError,
} = useOrganizationSettings();

const notificationStore = useNotificationStore();

const provider = ref<'smtp' | 'resend' | 'sendgrid' | 'mailgun'>('smtp');

const smtpHost = ref('');
const smtpPort = ref(587);
const smtpUsername = ref('');
const smtpPassword = ref('');
const smtpEncryption = ref<'tls' | 'ssl' | 'none'>('tls');

const fromEmail = ref('');
const fromName = ref('');
const replyToEmail = ref('');

const resendApiKey = ref('');

const sendTestEmail = ref(false);
const testEmailTo = ref('');

const saving = ref(false);
const testing = ref(false);
const testResult = ref<{ success: boolean; message: string } | null>(null);

const providerLabels: Record<string, string> = {
  smtp: 'SMTP Personalizado',
  resend: 'Resend',
  sendgrid: 'SendGrid',
  mailgun: 'Mailgun',
};

onMounted(() => {
  if (email.value) {
    provider.value = email.value.provider;
    smtpHost.value = email.value.smtp_host || '';
    smtpPort.value = email.value.smtp_port || 587;
    smtpUsername.value = email.value.smtp_username || '';
    smtpPassword.value = email.value.smtp_password || '';
    smtpEncryption.value = email.value.smtp_encryption || 'tls';
    fromEmail.value = email.value.from_email || '';
    fromName.value = email.value.from_name || '';
    replyToEmail.value = email.value.reply_to_email || '';
    resendApiKey.value = email.value.resend_api_key || '';
  }
});

const hasChanges = computed(() => {
  if (!email.value) return true;
  return (
    provider.value !== email.value.provider ||
    smtpHost.value !== (email.value.smtp_host || '') ||
    smtpPort.value !== (email.value.smtp_port || 587) ||
    smtpUsername.value !== (email.value.smtp_username || '') ||
    smtpEncryption.value !== (email.value.smtp_encryption || 'tls') ||
    fromEmail.value !== email.value.from_email ||
    fromName.value !== email.value.from_name ||
    replyToEmail.value !== (email.value.reply_to_email || '') ||
    resendApiKey.value !== (email.value.resend_api_key || '')
  );
});

const canSave = computed(() => {
  if (provider.value === 'smtp') {
    return smtpHost.value && smtpPort.value && smtpUsername.value && fromEmail.value;
  }
  if (provider.value === 'resend') {
    return resendApiKey.value && fromEmail.value;
  }
  return fromEmail.value;
});

async function saveEmail() {
  if (!canSave.value) return;

  saving.value = true;
  testResult.value = null;
  clearError();

  try {
    const data: any = {
      provider: provider.value,
      from_email: fromEmail.value,
      from_name: fromName.value,
      reply_to_email: replyToEmail.value || null,
      send_test_email: sendTestEmail.value,
      test_email_to: testEmailTo.value || null,
    };

    if (provider.value === 'smtp') {
      data.smtp_host = smtpHost.value;
      data.smtp_port = smtpPort.value;
      data.smtp_username = smtpUsername.value;
      data.smtp_password = smtpPassword.value || null;
      data.smtp_encryption = smtpEncryption.value;
    } else if (provider.value === 'resend') {
      data.resend_api_key = resendApiKey.value;
    }

    await updateEmailConfig(data);
    notificationStore.success('Configurações de e-mail salvas');

    if (sendTestEmail.value && testEmailTo.value) {
      testResult.value = { success: true, message: 'E-mail de teste enviado com sucesso!' };
    }
  } catch (err: any) {
    notificationStore.error(err.response?.data?.message || 'Erro ao salvar configurações de e-mail');
    if (sendTestEmail.value) {
      testResult.value = { success: false, message: err.response?.data?.message || 'Erro ao enviar e-mail de teste' };
    }
  } finally {
    saving.value = false;
    sendTestEmail.value = false;
  }
}

async function sendTestEmailOnly() {
  if (!testEmailTo.value || !canSave.value) return;

  testing.value = true;
  testResult.value = null;

  try {
    const data: any = {
      provider: provider.value,
      from_email: fromEmail.value,
      from_name: fromName.value,
      reply_to_email: replyToEmail.value || null,
      send_test_email: true,
      test_email_to: testEmailTo.value,
    };

    if (provider.value === 'smtp') {
      data.smtp_host = smtpHost.value;
      data.smtp_port = smtpPort.value;
      data.smtp_username = smtpUsername.value;
      data.smtp_password = smtpPassword.value || null;
      data.smtp_encryption = smtpEncryption.value;
    } else if (provider.value === 'resend') {
      data.resend_api_key = resendApiKey.value;
    }

    const result = await testEmail(data);
    testResult.value = { success: result.success, message: result.message };
    if (result.success) {
      notificationStore.success('E-mail de teste enviado!');
    } else {
      notificationStore.error(result.message);
    }
  } catch (err: any) {
    testResult.value = { success: false, message: err.response?.data?.message || 'Erro ao enviar e-mail de teste' };
    notificationStore.error(testResult.value.message);
  } finally {
    testing.value = false;
  }
}

function getProviderIcon(provider: string) {
  switch (provider) {
    case 'smtp': return 'server';
    case 'resend': return 'paper-airplane';
    case 'sendgrid': return 'cloud';
    case 'mailgun': return 'bolt';
    default: return 'server';
  }
}
</script>

<template>
  <div class="space-y-8 max-w-3xl">
    <div>
      <h2 class="text-xl font-semibold text-gray-900">Configurações de E-mail</h2>
      <p class="text-gray-600 mt-1">Configure o provedor de e-mail para envios transacionais e notificações</p>
    </div>

    <div v-if="settingsError" class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center justify-between">
      <span>{{ settingsError }}</span>
      <button @click="clearError" class="text-red-500 hover:text-red-700">✕</button>
    </div>

    <!-- Provider Selection -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <span>Provedor de E-mail</span>
      </h3>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <label
          v-for="prov in ['smtp', 'resend', 'sendgrid', 'mailgun']"
          :key="prov"
          class="relative cursor-pointer"
        >
          <input
            type="radio"
            :name="provider"
            :value="prov"
            v-model="provider"
            class="sr-only peer"
            @change="testResult.value = null"
          />
          <div class="relative p-4 border-2 rounded-xl text-center transition-all"
            :class="[
              provider === prov
                ? 'border-primary-500 bg-primary-50'
                : 'border-gray-200 hover:border-gray-300',
            ]"
          >
            <div class="w-12 h-12 mx-auto mb-3 rounded-lg flex items-center justify-center"
              :class="provider === prov ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-500'">
              <component :is="() => import(`@heroicons/vue/24/outline/${getProviderIcon(prov)}Icon`)" class="w-6 h-6" />
            </div>
            <p class="font-medium text-gray-900">{{ providerLabels[prov] }}</p>
          </div>
        </label>
      </div>
    </section>

    <!-- SMTP Configuration -->
    <section v-if="provider === 'smtp'" class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
        </svg>
        <span>Configuração SMTP</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="smtp-host" class="block text-sm font-medium text-gray-700 mb-1">Servidor SMTP</label>
          <input
            id="smtp-host"
            type="text"
            v-model="smtpHost"
            placeholder="smtp.exemplo.com"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-label="Endereço do servidor SMTP"
          />
        </div>

        <div>
          <label for="smtp-port" class="block text-sm font-medium text-gray-700 mb-1">Porta</label>
          <input
            id="smtp-port"
            type="number"
            v-model.number="smtpPort"
            min="1"
            max="65535"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-label="Porta do servidor SMTP"
          />
        </div>

        <div>
          <label for="smtp-username" class="block text-sm font-medium text-gray-700 mb-1">Usuário</label>
          <input
            id="smtp-username"
            type="text"
            v-model="smtpUsername"
            placeholder="usuario@exemplo.com"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-label="Usuário SMTP"
          />
        </div>

        <div>
          <label for="smtp-password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
          <input
            id="smtp-password"
            type="password"
            v-model="smtpPassword"
            placeholder="••••••••"
            autocomplete="current-password"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-label="Senha SMTP"
          />
          <p class="mt-1 text-xs text-gray-500">Deixe em branco para manter a senha atual</p>
        </div>

        <div>
          <label for="smtp-encryption" class="block text-sm font-medium text-gray-700 mb-1">Criptografia</label>
          <select
            id="smtp-encryption"
            v-model="smtpEncryption"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white"
            aria-label="Tipo de criptografia SMTP"
          >
            <option value="tls">TLS (STARTTLS) - Porta 587</option>
            <option value="ssl">SSL/TLS - Porta 465</option>
            <option value="none">Nenhuma - Porta 25</option>
          </select>
        </div>
      </div>
    </section>

    <!-- Resend Configuration -->
    <section v-if="provider === 'resend'" class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <span>Configuração Resend</span>
      </h3>

      <div>
        <label for="resend-api-key" class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
        <input
          id="resend-api-key"
          type="password"
          v-model="resendApiKey"
          placeholder="re_••••••••••••••••••••••••"
          autocomplete="current-password"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 font-mono"
          aria-label="Resend API Key"
        />
        <p class="mt-1 text-xs text-gray-500">Obtenha sua API key em <a href="https://resend.com/api-keys" target="_blank" rel="noopener" class="text-primary-600 hover:underline">resend.com/api-keys</a></p>
      </div>
    </section>

    <!-- Common Email Settings -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <span>Remetente Padrão</span>
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label for="from-email" class="block text-sm font-medium text-gray-700 mb-1">E-mail do Remetente <span class="text-red-500">*</span></label>
          <input
            id="from-email"
            type="email"
            v-model="fromEmail"
            placeholder="noreply@seudominio.com"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            required
            aria-label="E-mail do remetente"
          />
        </div>

        <div>
          <label for="from-name" class="block text-sm font-medium text-gray-700 mb-1">Nome do Remetente</label>
          <input
            id="from-name"
            type="text"
            v-model="fromName"
            placeholder="Sua Empresa"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-label="Nome do remetente"
          />
        </div>

        <div>
          <label for="reply-to" class="block text-sm font-medium text-gray-700 mb-1">E-mail de Resposta</label>
          <input
            id="reply-to"
            type="email"
            v-model="replyToEmail"
            placeholder="suporte@seudominio.com"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-label="E-mail para respostas"
          />
          <p class="mt-1 text-xs text-gray-500">Opcional. Se vazio, usa o e-mail do remetente</p>
        </div>
      </div>
    </section>

    <!-- Test Email Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <span>Teste de E-mail</span>
      </h3>

      <div class="space-y-4">
        <div>
          <label for="test-email" class="block text-sm font-medium text-gray-700 mb-1">E-mail para Teste</label>
          <input
            id="test-email"
            type="email"
            v-model="testEmailTo"
            placeholder="seu@email.com"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            aria-label="E-mail para receber teste"
          />
        </div>

        <div class="flex items-center">
          <input
            type="checkbox"
            id="send-test"
            v-model="sendTestEmail"
            class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
          />
          <label for="send-test" class="ml-3 text-sm text-gray-700 cursor-pointer">
            Enviar e-mail de teste ao salvar
          </label>
        </div>

        <button
          type="button"
          @click="sendTestEmailOnly"
          :disabled="testing || !testEmailTo.value || !canSave"
          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <svg v-if="testing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span>{{ testing ? 'Enviando...' : 'Enviar E-mail de Teste' }}</span>
        </button>

        <div v-if="testResult" :class="['p-4 rounded-lg', testResult.success ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800']">
          <div class="flex items-center space-x-2">
            <svg v-if="testResult.success" class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <svg v-else class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span>{{ testResult.message }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Status -->
    <section v-if="email.value" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
      <h3 class="text-lg font-medium text-gray-900">Status Atual</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="text-gray-500">Provedor</p>
          <p class="font-medium text-gray-900 capitalize">{{ providerLabels[email.value.provider] || email.value.provider }}</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="text-gray-500">Último teste</p>
          <p class="font-medium text-gray-900">{{ email.value.test_email_sent_at ? new Date(email.value.test_email_sent_at).toLocaleString('pt-BR') : 'Nunca' }}</p>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg">
          <p class="text-gray-500">Status do teste</p>
          <p class="font-medium" :class="email.value.test_email_status === 'success' ? 'text-green-600' : email.value.test_email_status === 'failed' ? 'text-red-600' : 'text-gray-500'">
            {{ email.value.test_email_status === 'success' ? 'Sucesso' : email.value.test_email_status === 'failed' ? 'Falhou' : email.value.test_email_status === 'pending' ? 'Pendente' : 'Não testado' }}
          </p>
        </div>
      </div>

      <div v-if="email.value.test_email_error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
        <strong>Erro:</strong> {{ email.value.test_email_error }}
      </div>
    </section>

    <!-- Save Button -->
    <div class="flex justify-end pt-4 border-t border-gray-100">
      <button
        type="button"
        @click="saveEmail"
        :disabled="saving || loading.value || !hasChanges || !canSave"
        class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
      >
        <svg v-if="saving" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <span>{{ saving ? 'Salvando...' : 'Salvar Configurações' }}</span>
      </button>
    </div>
  </div>
</template>