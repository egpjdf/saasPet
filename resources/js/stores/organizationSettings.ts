import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type {
  OrganizationSettings,
  BrandingConfig,
  DomainConfig,
  LocalizationConfig,
  FeaturesConfig,
  EmailConfig,
  FeatureFlag,
} from '@/Types/organizationSettings';

export const useOrganizationSettingsStore = defineStore('organizationSettings', () => {
  const settings = ref<OrganizationSettings | null>(null);
  const branding = ref<BrandingConfig | null>(null);
  const domain = ref<DomainConfig | null>(null);
  const localization = ref<LocalizationConfig | null>(null);
  const features = ref<FeaturesConfig | null>(null);
  const email = ref<EmailConfig | null>(null);

  const availableFeatures = ref<FeatureFlag[]>([]);
  const timezones = ref<string[]>([]);
  const locales = ref<{ code: string; name: string; native_name: string }[]>([]);
  const currencies = ref<{ code: string; name: string; symbol: string }[]>([]);

  const loading = ref<Record<string, boolean>>({});
  const error = ref<string | null>(null);

  const isLoaded = computed(() => settings.value !== null);

  const enabledFeatures = computed(() => features.value?.enabled_features || []);
  const isFeatureEnabled = computed(() => (key: string) => enabledFeatures.value.includes(key));

  const currentPlan = computed(() => features.value?.plan || 'free');

  function setSettings(data: OrganizationSettings) {
    settings.value = data;
    branding.value = data.branding;
    domain.value = data.domain;
    localization.value = data.localization;
    features.value = data.features;
    email.value = data.email;
  }

  function setBranding(data: BrandingConfig) {
    branding.value = data;
    if (settings.value) {
      settings.value.branding = data;
    }
  }

  function setDomain(data: DomainConfig) {
    domain.value = data;
    if (settings.value) {
      settings.value.domain = data;
    }
  }

  function setLocalization(data: LocalizationConfig) {
    localization.value = data;
    if (settings.value) {
      settings.value.localization = data;
    }
  }

  function setFeatures(data: FeaturesConfig) {
    features.value = data;
    if (settings.value) {
      settings.value.features = data;
    }
  }

  function setAvailableFeatures(data: FeatureFlag[]) {
    availableFeatures.value = data;
  }

  function setEmail(data: EmailConfig) {
    email.value = data;
    if (settings.value) {
      settings.value.email = data;
    }
  }

  function setTimezones(data: string[]) {
    timezones.value = data;
  }

  function setLocales(data: { code: string; name: string; native_name: string }[]) {
    locales.value = data;
  }

  function setCurrencies(data: { code: string; name: string; symbol: string }[]) {
    currencies.value = data;
  }

  function setLoading(key: string, value: boolean) {
    loading.value[key] = value;
  }

  function setError(message: string | null) {
    error.value = message;
  }

  function clearError() {
    error.value = null;
  }

  function clear() {
    settings.value = null;
    branding.value = null;
    domain.value = null;
    localization.value = null;
    features.value = null;
    email.value = null;
    availableFeatures.value = [];
    timezones.value = [];
    locales.value = [];
    currencies.value = [];
    loading.value = {};
    error.value = null;
  }

  return {
    settings,
    branding,
    domain,
    localization,
    features,
    email,
    availableFeatures,
    timezones,
    locales,
    currencies,
    loading,
    error,
    isLoaded,
    enabledFeatures,
    isFeatureEnabled,
    currentPlan,
    setSettings,
    setBranding,
    setDomain,
    setLocalization,
    setFeatures,
    setAvailableFeatures,
    setEmail,
    setTimezones,
    setLocales,
    setCurrencies,
    setLoading,
    setError,
    clearError,
    clear,
  };
});