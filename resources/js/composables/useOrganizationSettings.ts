import { ref, computed } from 'vue';
import { useOrganizationSettingsStore } from '@/Stores/organizationSettings';
import type { OrganizationSettings, BrandingConfig, DomainConfig, LocalizationConfig, FeaturesConfig, EmailConfig } from '@/Types/organizationSettings';
import {
  fetchOrganizationSettings as apiFetch,
  updateOrganizationSettings as apiUpdate,
  updateBranding as apiUpdateBranding,
  updateDomain as apiUpdateDomain,
  updateLocalization as apiUpdateLocalization,
  fetchAvailableFeatures as apiFetchFeatures,
  updateEmail as apiUpdateEmail,
} from '@/Services/api/organizationSettings';

export function useOrganizationSettings() {
  const store = useOrganizationSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);

  const settings = computed(() => store.settings);
  const branding = computed(() => store.branding);
  const domain = computed(() => store.domain);
  const localization = computed(() => store.localization);
  const features = computed(() => store.features);
  const email = computed(() => store.email);
  const isLoaded = computed(() => store.isLoaded);

  async function load(): Promise<OrganizationSettings | null> {
    if (isLoaded.value) return store.settings;

    loading.value = true;
    error.value = null;

    try {
      const data = await apiFetch();
      store.setSettings(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar configurações da organização';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateSettings(data: Partial<OrganizationSettings>): Promise<OrganizationSettings> {
    loading.value = true;
    error.value = null;

    try {
      const result = await apiUpdate(data);
      store.setSettings(result);
      return result;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar configurações';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function updateBrandingConfig(data: BrandingConfig): Promise<BrandingConfig> {
    store.setLoading('branding', true);
    error.value = null;

    try {
      const result = await apiUpdateBranding(data);
      store.setBranding(result);
      return result;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar identidade visual';
      throw err;
    } finally {
      store.setLoading('branding', false);
    }
  }

  async function updateDomainConfig(data: DomainConfig): Promise<DomainConfig> {
    store.setLoading('domain', true);
    error.value = null;

    try {
      const result = await apiUpdateDomain(data);
      store.setDomain(result);
      return result;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar domínio';
      throw err;
    } finally {
      store.setLoading('domain', false);
    }
  }

  async function updateLocalizationConfig(data: LocalizationConfig): Promise<LocalizationConfig> {
    store.setLoading('localization', true);
    error.value = null;

    try {
      const result = await apiUpdateLocalization(data);
      store.setLocalization(result);
      return result;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar localização';
      throw err;
    } finally {
      store.setLoading('localization', false);
    }
  }

  async function loadFeatures(): Promise<FeaturesConfig> {
    store.setLoading('features', true);
    error.value = null;

    try {
      const data = await apiFetchFeatures();
      store.setFeatures(data);
      store.setAvailableFeatures(data.available_features);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar funcionalidades';
      throw err;
    } finally {
      store.setLoading('features', false);
    }
  }

  async function updateEmailConfig(data: EmailConfig): Promise<EmailConfig> {
    store.setLoading('email', true);
    error.value = null;

    try {
      const result = await apiUpdateEmail(data);
      store.setEmail(result);
      return result;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar configurações de e-mail';
      throw err;
    } finally {
      store.setLoading('email', false);
    }
  }

  function clearError() {
    error.value = null;
    store.clearError();
  }

  return {
    settings,
    branding,
    domain,
    localization,
    features,
    email,
    loading,
    error,
    isLoaded,
    load,
    updateSettings,
    updateBrandingConfig,
    updateDomainConfig,
    updateLocalizationConfig,
    loadFeatures,
    updateEmailConfig,
    clearError,
  };
}