import { ref, computed } from 'vue';
import { useOrganizationSettingsStore } from '@/Stores/organizationSettings';
import { fetchAvailableFeatures } from '@/Services/api/organizationSettings';
import type { FeatureFlag, FeaturesConfig } from '@/Types/organizationSettings';

export interface FeatureFlagWithStatus extends FeatureFlag {
  canEnable: boolean;
  dependentFeatures: FeatureFlag[];
  planBadge: string;
  planColor: string;
}

export function useFeatureFlags() {
  const store = useOrganizationSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);

  const features = computed(() => store.availableFeatures);
  const enabledFeatures = computed(() => store.enabledFeatures);
  const currentPlan = computed(() => store.currentPlan);
  const isFeatureEnabled = computed(() => store.isFeatureEnabled);

  const planLabels: Record<string, string> = {
    free: 'Gratuito',
    starter: 'Starter',
    professional: 'Professional',
    enterprise: 'Enterprise',
  };

  const planColors: Record<string, string> = {
    free: 'bg-gray-100 text-gray-700',
    starter: 'bg-green-100 text-green-700',
    professional: 'bg-blue-100 text-blue-700',
    enterprise: 'bg-purple-100 text-purple-700',
  };

  const categoryLabels: Record<string, string> = {
    core: 'Essenciais',
    communication: 'Comunicação',
    analytics: 'Analytics & Relatórios',
    integrations: 'Integrações',
    automation: 'Automação',
    branding: 'Marca & Domínio',
    email: 'E-mail & Notificações',
  };

  const categoryIcons: Record<string, string> = {
    core: 'cube',
    communication: 'chat-bubble-left-right',
    analytics: 'chart-bar',
    integrations: 'puzzle-piece',
    automation: 'bolt',
    branding: 'paint-brush',
    email: 'envelope',
  };

  const featuresByCategory = computed(() => {
    const categories = ['core', 'communication', 'analytics', 'integrations', 'automation', 'branding', 'email'] as const;
    const result: Record<string, FeatureFlagWithStatus[]> = {};
    categories.forEach((cat) => {
      result[cat] = features.value
        .filter((f) => f.category === cat)
        .map((f) => enhanceFeature(f));
    });
    return result;
  });

  function enhanceFeature(feature: FeatureFlag): FeatureFlagWithStatus {
    const enabled = isFeatureEnabled.value(feature.key);
    const deps = feature.dependencies || [];
    const dependentFeatures = features.value.filter((f) => f.dependencies?.includes(feature.key));

    return {
      ...feature,
      enabled,
      canEnable: !enabled && deps.every((depKey) => isFeatureEnabled.value(depKey)),
      dependentFeatures: dependentFeatures.map(enhanceFeature),
      planBadge: planLabels[feature.required_plan] || feature.required_plan,
      planColor: planColors[feature.required_plan] || 'bg-gray-100 text-gray-700',
    };
  }

  const getDependentFeatures = computed(() => (featureKey: string) => {
    return features.value
      .filter((f) => f.dependencies?.includes(featureKey))
      .map(enhanceFeature);
  });

  const canEnableFeature = computed(() => (feature: FeatureFlagWithStatus) => {
    if (feature.enabled) return false;
    if (!feature.dependencies || feature.dependencies.length === 0) return true;
    return feature.dependencies.every((depKey) => isFeatureEnabled.value(depKey));
  });

  async function load(): Promise<FeaturesConfig | null> {
    loading.value = true;
    error.value = null;

    try {
      const data = await fetchAvailableFeatures();
      store.setFeatures(data);
      store.setAvailableFeatures(data.available_features);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar funcionalidades';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function clearError() {
    error.value = null;
  }

  function getDependencyTooltip(feature: FeatureFlagWithStatus): string {
    if (!feature.dependencies || feature.dependencies.length === 0) return '';
    const deps = feature.dependencies.map((key) => {
      const dep = features.value.find((f) => f.key === key);
      return dep?.name || key;
    });
    return `Requer: ${deps.join(', ')}`;
  }

  function getDependentsTooltip(feature: FeatureFlagWithStatus): string {
    if (feature.dependentFeatures.length === 0) return '';
    return `Habilita: ${feature.dependentFeatures.map((f) => f.name).join(', ')}`;
  }

  function getUpgradeTooltip(feature: FeatureFlagWithStatus): string {
    if (feature.enabled) return '';
    const planOrder = ['free', 'starter', 'professional', 'enterprise'];
    const currentPlanIndex = planOrder.indexOf(currentPlan.value);
    const requiredPlanIndex = planOrder.indexOf(feature.required_plan);
    if (requiredPlanIndex <= currentPlanIndex) return '';
    return `Requer plano ${planLabels[feature.required_plan] || feature.required_plan} ou superior`;
  }

  return {
    features,
    featuresByCategory,
    enabledFeatures,
    currentPlan,
    loading,
    error,
    planLabels,
    planColors,
    categoryLabels,
    categoryIcons,
    load,
    clearError,
    canEnableFeature,
    getDependentFeatures,
    enhanceFeature,
    getDependencyTooltip,
    getDependentsTooltip,
    getUpgradeTooltip,
  };
}