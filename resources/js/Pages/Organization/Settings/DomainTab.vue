<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useOrganizationSettings } from '@/Composables/useOrganizationSettings';
import { useDomainValidation } from '@/Composables/useDomainValidation';
import { useNotificationStore } from '@/Stores/notification';

const {
  domain,
  loading,
  updateDomainConfig,
  error: settingsError,
  clearError,
} = useOrganizationSettings();

const notificationStore = useNotificationStore();

const {
  state: validationState,
  isChecking,
  sslBadgeClass,
  cnameBadgeClass,
  validateDomain,
  checkSsl,
  stopPolling,
  setInitialState,
} = useDomainValidation();

const customDomain = ref('');
const useCustomDomain = ref(false);
const verifying = ref(false);

onMounted(() => {
  if (domain.value) {
    customDomain.value = domain.value.custom_domain || '';
    useCustomDomain.value = domain.value.use_custom_domain || false;
    setInitialState(domain.value.cname_status, domain.value.ssl_status, domain.value.ssl_expires_at);
  }
});

onUnmounted(() => {
  stopPolling();
});

const domainError = computed(() => {
  if (!customDomain.value) return null;
  const hostname = customDomain.value.replace(/^https?:\/\//, '').split('/')[0];
  if (hostname.length > 253) return 'Domínio muito longo (máx. 253 caracteres)';
  if (!/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(hostname)) return 'Formato de domínio inválido';
  return null;
});

const canSave = computed(() => !domainError.value && (customDomain.value !== (domain.value?.custom_domain || '') || useCustomDomain.value !== domain.value?.use_custom_domain));

async function handleVerifyCname() {
  if (!customDomain.value || domainError.value) return;
  verifying.value = true;
  try {
    await validateDomain(customDomain.value);
    notificationStore.success('Verificação de CNAME iniciada');
  } catch {
    notificationStore.error('Erro ao verificar CNAME');
  } finally {
    verifying.value = false;
  }
}

async function handleSslCheck() {
  if (!customDomain.value) return;
  try {
    await checkSsl(customDomain.value);
  } catch {
    // Error handled in composable
  }
}

async function saveDomain() {
  if (!canSave.value) return;

  try {
    const data = {
      custom_domain: customDomain.value || null,
      use_custom_domain: useCustomDomain.value,
    };
    await updateDomainConfig(data);
    notificationStore.success('Configurações de domínio salvas');
  } catch (err: any) {
    notificationStore.error(err.response?.data?.message || 'Erro ao salvar domínio');
  }
}

function getCnameInstructions() {
  if (!domain.value?.verification_token) return null;
  return {
    type: 'CNAME',
    host: domain.value.verification_token,
    value: 'verify.saaspet.com',
  };
}
</script>

<template>
  <div class="space-y-8 max-w-3xl">
    <div>
      <h2 class="text-xl font-semibold text-gray-900">Domínio Personalizado</h2>
      <p class="text-gray-600 mt-1">Configure um domínio próprio para sua organização</p>
    </div>

    <div v-if="settingsError" class="px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm flex items-center justify-between">
      <span>{{ settingsError }}</span>
      <button @click="clearError" class="text-red-500 hover:text-red-700">✕</button>
    </div>

    <!-- Domain Input Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-1.343 3-3V6a3 3 0 00-3-3H6a3 3 0 00-3 3v3a3 3 0 003 3h3" />
        </svg>
        <span>Configuração de Domínio</span>
      </h3>

      <div class="space-y-4">
        <div>
          <label for="custom-domain" class="block text-sm font-medium text-gray-700 mb-1">Domínio Personalizado</label>
          <div class="relative">
            <input
              id="custom-domain"
              type="text"
              v-model="customDomain"
              placeholder="ex: app.minhaempresa.com"
              class="w-full px-4 py-3 pr-12 border rounded-lg text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              :class="{ 'border-red-300': domainError.value, 'border-gray-300': !domainError.value }"
              aria-describedby="domain-help"
              @blur="handleSslCheck"
            />
            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500">https://</span>
          </div>
          <p v-if="domainError.value" id="domain-help" class="mt-1 text-sm text-red-600" role="alert">{{ domainError.value }}</p>
          <p v-else id="domain-help" class="mt-1 text-sm text-gray-500">Use apenas subdomínios (ex: app.seudominio.com). Não use domínio raiz.</p>
        </div>

        <div class="flex items-center">
          <input
            type="checkbox"
            id="use-custom-domain"
            v-model="useCustomDomain"
            :disabled="!customDomain.value || domainError.value"
            class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
          />
          <label for="use-custom-domain" class="ml-3 text-sm text-gray-700 cursor-pointer">
            Usar domínio personalizado (requer CNAME válido e SSL ativo)
          </label>
        </div>
      </div>
    </section>

    <!-- CNAME Verification Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Verificação CNAME</span>
      </h3>

      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">Status do CNAME</p>
            <p class="text-sm text-gray-500">Verifica se o DNS está configurado corretamente</p>
          </div>
          <span :class="['px-3 py-1 text-xs font-medium rounded-full', cnameBadgeClass.value]">
            {{ validationState.cnameStatus === 'valid' ? 'Válido' : validationState.cnameStatus === 'invalid' ? 'Inválido' : validationState.cnameStatus === 'pending' ? 'Verificando...' : 'Não configurado' }}
          </span>
        </div>

        <div v-if="validationState.cnameMessage" class="p-3 bg-gray-50 rounded-lg text-sm" :class="{ 'text-green-700': validationState.cnameStatus === 'valid', 'text-red-700': validationState.cnameStatus === 'invalid', 'text-blue-700': validationState.cnameStatus === 'pending', 'text-gray-700': validationState.cnameStatus === 'not_configured' }">
          {{ validationState.cnameMessage }}
        </div>

        <button
          type="button"
          @click="handleVerifyCname"
          :disabled="!customDomain.value || domainError.value || verifying.value || isChecking.value"
          class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <svg v-if="verifying.value || isChecking.value" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span>{{ verifying.value || isChecking.value ? 'Verificando...' : 'Verificar CNAME' }}</span>
        </button>

        <!-- CNAME Instructions -->
        <div v-if="getCnameInstructions()" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <h4 class="text-sm font-medium text-blue-900 mb-2 flex items-center space-x-1">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <span>Configuração DNS Necessária</span>
          </h4>
          <div class="space-y-2 text-sm text-blue-800 font-mono">
            <div class="bg-blue-100 p-2 rounded">
              <strong>Tipo:</strong> {{ getCnameInstructions().type }}
            </div>
            <div class="bg-blue-100 p-2 rounded">
              <strong>Host/Name:</strong> {{ getCnameInstructions().host }}
            </div>
            <div class="bg-blue-100 p-2 rounded">
              <strong>Valor/Target:</strong> {{ getCnameInstructions().value }}
            </div>
          </div>
          <p class="mt-2 text-xs text-blue-700">Adicione este registro CNAME no painel do seu provedor de DNS. A verificação pode levar até 48h.</p>
        </div>

        <div v-if="validationState.lastChecked" class="text-xs text-gray-500">
          Última verificação: {{ validationState.lastChecked.toLocaleString('pt-BR') }}
        </div>
      </div>
    </section>

    <!-- SSL Status Section -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h3 class="text-lg font-medium text-gray-900 flex items-center space-x-2">
        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        <span>Status do SSL/TLS</span>
      </h3>

      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-900">Certificado SSL</p>
            <p class="text-sm text-gray-500">Status do certificado de segurança</p>
          </div>
          <span :class="['px-3 py-1 text-xs font-medium rounded-full', sslBadgeClass.value]">
            {{ validationState.sslStatus === 'valid' ? 'Válido' : validationState.sslStatus === 'expiring' ? 'Expirando' : validationState.sslStatus === 'expired' ? 'Expirado' : validationState.sslStatus === 'pending' ? 'Provisionando...' : 'Não configurado' }}
          </span>
        </div>

        <div v-if="validationState.sslExpiresAt" class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700">
          <p>Expira em: <strong>{{ new Date(validationState.sslExpiresAt).toLocaleDateString('pt-BR') }}</strong></p>
          <p v-if="validationState.sslDaysUntilExpiry !== null">
            <span :class="validationState.sslDaysUntilExpiry < 30 ? 'text-red-600' : 'text-gray-600'">
              {{ validationState.sslDaysUntilExpiry }} dias restantes
            </span>
          </p>
        </div>

        <div v-if="validationState.sslStatus === 'expiring'" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
          <strong>Atenção:</strong> O certificado SSL expira em menos de 30 dias. A renovação automática será tentada.
        </div>

        <div v-if="validationState.sslStatus === 'expired'" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
          <strong>Erro:</strong> O certificado SSL expirou. O domínio personalizado não funcionará até a renovação.
        </div>
      </div>
    </section>

    <!-- Current Domain Info -->
    <section v-if="domain.value?.custom_domain" class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
      <h3 class="text-lg font-medium text-gray-900">Domínio Atual</h3>
      <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-1.343 3-3V6a3 3 0 00-3-3H6a3 3 0 00-3 3v3a3 3 0 003 3h3" />
            </svg>
          </div>
          <div>
            <p class="font-mono text-gray-900">{{ domain.value.custom_domain }}</p>
            <p class="text-sm text-gray-500">Domínio personalizado ativo</p>
          </div>
        </div>
        <span class="px-3 py-1 text-xs font-medium text-green-600 bg-green-50 rounded-full">Ativo</span>
      </div>
    </section>

    <!-- Save Button -->
    <div class="flex justify-end pt-4 border-t border-gray-100">
      <button
        type="button"
        @click="saveDomain"
        :disabled="!canSave.value || loading.value"
        class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
      >
        <span>Salvar Configurações</span>
      </button>
    </div>
  </div>
</template>