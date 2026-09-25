import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useOrganizationSettingsStore } from '@/Stores/organizationSettings';
import { useFileUpload } from '@/Composables/useFileUpload';
import { useDomainValidation } from '@/Composables/useDomainValidation';
import { useFeatureFlags } from '@/Composables/useFeatureFlags';

vi.mock('@/Services/api/organizationSettings', () => ({
  fetchOrganizationSettings: vi.fn(),
  updateOrganizationSettings: vi.fn(),
  updateBranding: vi.fn(),
  updateDomain: vi.fn(),
  updateLocalization: vi.fn(),
  fetchAvailableFeatures: vi.fn(),
  updateEmail: vi.fn(),
  uploadBrandingFile: vi.fn(),
  validateCname: vi.fn(),
  checkSslStatus: vi.fn(),
  fetchTimezones: vi.fn(),
  fetchLocales: vi.fn(),
  fetchCurrencies: vi.fn(),
}));

vi.mock('@/Stores/tenant', () => ({
  useTenantStore: () => ({
    organization: { id: 'org-1', slug: 'test', name: 'Test Org' },
    workspace: null,
  }),
}));

describe('useOrganizationSettingsStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('should initialize with empty state', () => {
    const store = useOrganizationSettingsStore();
    expect(store.settings).toBeNull();
    expect(store.branding).toBeNull();
    expect(store.domain).toBeNull();
    expect(store.localization).toBeNull();
    expect(store.features).toBeNull();
    expect(store.email).toBeNull();
    expect(store.isLoaded).toBe(false);
  });

  it('should set settings and update all sub-states', () => {
    const store = useOrganizationSettingsStore();
    const mockSettings = {
      id: '1',
      organization_id: 'org-1',
      branding: { logo_url: null, primary_color: '#3B82F6', secondary_color: '#64748B', accent_color: '#8B5CF6', custom_css: null },
      domain: { custom_domain: null, cname_status: 'not_configured', ssl_status: 'not_configured', use_custom_domain: false },
      localization: { timezone: 'America/Sao_Paulo', locale: 'pt-BR', currency: 'BRL', date_format: 'DD/MM/YYYY', time_format: '24h', first_day_of_week: 0 },
      features: { plan: 'free', enabled_features: [], available_features: [] },
      email: { provider: 'smtp', from_email: 'test@test.com', from_name: 'Test' },
      created_at: '2024-01-01',
      updated_at: '2024-01-01',
    };

    store.setSettings(mockSettings);

    expect(store.settings).toEqual(mockSettings);
    expect(store.branding).toEqual(mockSettings.branding);
    expect(store.domain).toEqual(mockSettings.domain);
    expect(store.localization).toEqual(mockSettings.localization);
    expect(store.features).toEqual(mockSettings.features);
    expect(store.email).toEqual(mockSettings.email);
    expect(store.isLoaded).toBe(true);
  });

  it('should update branding independently', () => {
    const store = useOrganizationSettingsStore();
    const newBranding = { logo_url: 'new-logo.png', primary_color: '#FF0000', secondary_color: '#00FF00', accent_color: '#0000FF', custom_css: '' };
    store.setBranding(newBranding);
    expect(store.branding).toEqual(newBranding);
  });

  it('should clear all state', () => {
    const store = useOrganizationSettingsStore();
    store.setSettings({} as any);
    store.clear();
    expect(store.settings).toBeNull();
    expect(store.branding).toBeNull();
  });
});

describe('useFileUpload', () => {
  it('should validate file size', () => {
    const { isValid, validationError, setFile } = useFileUpload({ maxSize: 1000 });
    const largeFile = new File(['x'.repeat(2000)], 'large.png', { type: 'image/png' });
    setFile(largeFile);
    expect(isValid.value).toBe(false);
    expect(validationError.value).toContain('muito grande');
  });

  it('should validate file type', () => {
    const { isValid, validationError, setFile } = useFileUpload({ acceptedTypes: ['image/png'] });
    const invalidFile = new File(['test'], 'test.txt', { type: 'text/plain' });
    setFile(invalidFile);
    expect(isValid.value).toBe(false);
    expect(validationError.value).toContain('não suportado');
  });

  it('should accept valid file', () => {
    const { isValid, validationError, preview, setFile, file } = useFileUpload();
    const validFile = new File(['test'], 'test.png', { type: 'image/png' });
    setFile(validFile);
    expect(isValid.value).toBe(true);
    expect(validationError.value).toBeNull();
    expect(file.value).not.toBeNull();
  });

  it('should clear file', () => {
    const { file, preview, error, setFile, clearFile } = useFileUpload();
    const validFile = new File(['test'], 'test.png', { type: 'image/png' });
    setFile(validFile);
    clearFile();
    expect(file.value).toBeNull();
    expect(preview.value).toBeNull();
    expect(error.value).toBeNull();
  });
});

describe('useDomainValidation', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('should initialize with default state', () => {
    const { state, isValid } = useDomainValidation();
    expect(state.value.cnameStatus).toBe('not_configured');
    expect(state.value.sslStatus).toBe('not_configured');
    expect(isValid.value).toBe(false);
  });

  it('should set initial state', () => {
    const { state, setInitialState } = useDomainValidation();
    setInitialState('valid', 'valid', '2025-12-31T23:59:59.000Z');
    expect(state.value.cnameStatus).toBe('valid');
    expect(state.value.sslStatus).toBe('valid');
    expect(state.value.sslExpiresAt).toBe('2025-12-31T23:59:59.000Z');
  });

  it('should reset state', () => {
    const { state, setInitialState, reset } = useDomainValidation();
    setInitialState('valid', 'valid', '2025-12-31T23:59:59.000Z');
    reset();
    expect(state.value.cnameStatus).toBe('not_configured');
    expect(state.value.sslStatus).toBe('not_configured');
  });
});

describe('useFeatureFlags', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('should compute features by category', () => {
    const store = useOrganizationSettingsStore();
    store.setAvailableFeatures([
      { key: 'feat1', name: 'Feature 1', description: 'Desc', enabled: true, required_plan: 'free', category: 'core', dependencies: [] },
      { key: 'feat2', name: 'Feature 2', description: 'Desc', enabled: false, required_plan: 'starter', category: 'communication', dependencies: [] },
    ]);
    // currentPlan is computed from features.plan, so we set it via setFeatures
    store.setFeatures({ plan: 'free', enabled_features: [], available_features: store.availableFeatures });

    const { featuresByCategory, canEnableFeature } = useFeatureFlags();

    expect(featuresByCategory.value.core).toHaveLength(1);
    expect(featuresByCategory.value.communication).toHaveLength(1);
    expect(canEnableFeature.value(featuresByCategory.value.communication[0])).toBe(true);
  });

  it('should check dependencies for canEnable', () => {
    const store = useOrganizationSettingsStore();
    store.setAvailableFeatures([
      { key: 'feat1', name: 'Feature 1', description: 'Desc', enabled: true, required_plan: 'free', category: 'core', dependencies: [] },
      { key: 'feat2', name: 'Feature 2', description: 'Desc', enabled: false, required_plan: 'free', category: 'core', dependencies: ['feat1'] },
      { key: 'feat3', name: 'Feature 3', description: 'Desc', enabled: false, required_plan: 'free', category: 'core', dependencies: ['feat1', 'feat2'] },
    ]);
    store.setFeatures({ plan: 'free', enabled_features: ['feat1'], available_features: store.availableFeatures });

    const { canEnableFeature } = useFeatureFlags();

    expect(canEnableFeature.value(store.availableFeatures[1])).toBe(true);
    expect(canEnableFeature.value(store.availableFeatures[2])).toBe(false);
  });

  it('should generate upgrade tooltip', () => {
    const store = useOrganizationSettingsStore();
    store.setAvailableFeatures([
      { key: 'feat1', name: 'Feature 1', description: 'Desc', enabled: false, required_plan: 'professional', category: 'core', dependencies: [] },
    ]);
    store.setFeatures({ plan: 'starter', enabled_features: [], available_features: store.availableFeatures });

    const { getUpgradeTooltip } = useFeatureFlags();

    const tooltip = getUpgradeTooltip(store.availableFeatures[0]);
    expect(tooltip).toContain('Professional');
  });
});