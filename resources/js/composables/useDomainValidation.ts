import { ref, computed } from 'vue';
import { validateCname, checkSslStatus } from '@/Services/api/organizationSettings';
import type { CnameValidationResponse, SslStatusResponse } from '@/Types/organizationSettings';

export interface DomainValidationState {
  cnameStatus: 'pending' | 'valid' | 'invalid' | 'not_configured';
  cnameMessage: string;
  sslStatus: 'valid' | 'expiring' | 'expired' | 'not_configured' | 'pending';
  sslExpiresAt: string | null;
  sslDaysUntilExpiry: number | null;
  lastChecked: Date | null;
  checking: boolean;
}

export function useDomainValidation() {
  const state = ref<DomainValidationState>({
    cnameStatus: 'not_configured',
    cnameMessage: '',
    sslStatus: 'not_configured',
    sslExpiresAt: null,
    sslDaysUntilExpiry: null,
    lastChecked: null,
    checking: false,
  });

  let pollingInterval: ReturnType<typeof setInterval> | null = null;
  let debounceTimer: ReturnType<typeof setTimeout> | null = null;

  const isValid = computed(() => state.value.cnameStatus === 'valid' && state.value.sslStatus !== 'expired');
  const isChecking = computed(() => state.value.checking);
  const sslBadgeClass = computed(() => {
    switch (state.value.sslStatus) {
      case 'valid': return 'bg-green-100 text-green-700';
      case 'expiring': return 'bg-yellow-100 text-yellow-700';
      case 'expired': return 'bg-red-100 text-red-700';
      case 'pending': return 'bg-blue-100 text-blue-700';
      default: return 'bg-gray-100 text-gray-700';
    }
  });

  const cnameBadgeClass = computed(() => {
    switch (state.value.cnameStatus) {
      case 'valid': return 'bg-green-100 text-green-700';
      case 'invalid': return 'bg-red-100 text-red-700';
      case 'pending': return 'bg-blue-100 text-blue-700';
      default: return 'bg-gray-100 text-gray-700';
    }
  });

  async function validateDomain(domain: string): Promise<CnameValidationResponse | null> {
    if (debounceTimer) clearTimeout(debounceTimer);

    return new Promise((resolve) => {
      debounceTimer = setTimeout(async () => {
        state.value.checking = true;
        state.value.cnameStatus = 'pending';
        state.value.cnameMessage = 'Verificando CNAME...';

        try {
          const result = await validateCname(domain);
          state.value.cnameStatus = result.status;
          state.value.cnameMessage = result.message;
          state.value.lastChecked = new Date();

          if (result.status === 'valid') {
            await checkSsl(domain);
            startPolling(domain);
          } else {
            stopPolling();
          }

          resolve(result);
        } catch (err: any) {
          state.value.cnameStatus = 'invalid';
          state.value.cnameMessage = err.response?.data?.message || 'Erro ao validar CNAME';
          state.value.lastChecked = new Date();
          stopPolling();
          resolve(null);
        } finally {
          state.value.checking = false;
        }
      }, 500);
    });
  }

  async function checkSsl(domain: string): Promise<SslStatusResponse | null> {
    try {
      const result = await checkSslStatus(domain);
      state.value.sslStatus = result.status;
      state.value.sslExpiresAt = result.expires_at;
      state.value.sslDaysUntilExpiry = result.days_until_expiry;
      return result;
    } catch {
      state.value.sslStatus = 'not_configured';
      state.value.sslExpiresAt = null;
      state.value.sslDaysUntilExpiry = null;
      return null;
    }
  }

  function startPolling(domain: string) {
    stopPolling();
    pollingInterval = setInterval(async () => {
      if (state.value.cnameStatus !== 'valid') {
        stopPolling();
        return;
      }
      await checkSsl(domain);
    }, 60000);
  }

  function stopPolling() {
    if (pollingInterval) {
      clearInterval(pollingInterval);
      pollingInterval = null;
    }
  }

  function reset() {
    stopPolling();
    if (debounceTimer) clearTimeout(debounceTimer);
    state.value = {
      cnameStatus: 'not_configured',
      cnameMessage: '',
      sslStatus: 'not_configured',
      sslExpiresAt: null,
      sslDaysUntilExpiry: null,
      lastChecked: null,
      checking: false,
    };
  }

  function setInitialState(cnameStatus: DomainValidationState['cnameStatus'], sslStatus: DomainValidationState['sslStatus'], sslExpiresAt: string | null) {
    state.value.cnameStatus = cnameStatus;
    state.value.sslStatus = sslStatus;
    state.value.sslExpiresAt = sslExpiresAt;
    if (cnameStatus === 'valid') {
      state.value.cnameMessage = 'CNAME configurado corretamente';
    }
    if (sslExpiresAt) {
      const diff = new Date(sslExpiresAt).getTime() - Date.now();
      state.value.sslDaysUntilExpiry = Math.ceil(diff / (1000 * 60 * 60 * 24));
    }
  }

  return {
    state,
    isValid,
    isChecking,
    sslBadgeClass,
    cnameBadgeClass,
    validateDomain,
    checkSsl,
    startPolling,
    stopPolling,
    reset,
    setInitialState,
  };
}