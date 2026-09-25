import { ref, computed } from 'vue';
import { useWorkspaceSettingsStore } from '@/Stores/workspaceSettings';
import type { WorkspaceFeature, FeatureToggleRequest } from '@/Types/workspaceSettings';
import { fetchWorkspaceFeatures as apiFetch, toggleFeature as apiToggle, fetchAvailableFeatures as apiAvailable } from '@/Services/api/workspaceFeatures';

export function useWorkspaceFeatures() {
  const store = useWorkspaceSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);
  const toggling = ref<Record<string, boolean>>({});

  const features = computed(() => store.features);
  const availableFeatures = computed(() => store.availableFeatures);

  const featuresByCategory = computed(() => {
    const categories = ['core', 'communication', 'analytics', 'integrations', 'automation'] as const;
    const result: Record<string, WorkspaceFeature[]> = {};
    categories.forEach(cat => {
      result[cat] = features.value.filter(f => f.category === cat);
    });
    return result;
  });

  const enabledFeatures = computed(() => features.value.filter(f => f.enabled));
  const disabledFeatures = computed(() => features.value.filter(f => !f.enabled));

  const canEnableFeature = computed(() => (feature: WorkspaceFeature) => {
    if (feature.enabled) return false;
    if (!feature.dependencies || feature.dependencies.length === 0) return true;
    return feature.dependencies.every(depKey => {
      const dep = features.value.find(f => f.key === depKey);
      return dep?.enabled === true;
    });
  });

  const getDependentFeatures = computed(() => (featureKey: string) => {
    return features.value.filter(f => f.dependencies?.includes(featureKey));
  });

  async function load(): Promise<WorkspaceFeature[]> {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await apiFetch();
      store.setFeatures(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar funcionalidades';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function loadAvailable(): Promise<WorkspaceFeature[]> {
    try {
      const data = await apiAvailable();
      store.setAvailableFeatures(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar funcionalidades disponíveis';
      throw err;
    }
  }

  async function toggle(request: FeatureToggleRequest): Promise<WorkspaceFeature> {
    toggling.value[request.feature_key] = true;
    error.value = null;
    
    try {
      const data = await apiToggle(request);
      store.updateFeature(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao alternar funcionalidade';
      throw err;
    } finally {
      toggling.value[request.feature_key] = false;
    }
  }

  function isToggling(featureKey: string): boolean {
    return !!toggling.value[featureKey];
  }

  function clearError() {
    error.value = null;
  }

  return {
    features,
    availableFeatures,
    featuresByCategory,
    enabledFeatures,
    disabledFeatures,
    canEnableFeature,
    getDependentFeatures,
    loading,
    error,
    toggling,
    load,
    loadAvailable,
    toggle,
    isToggling,
    clearError,
  };
}