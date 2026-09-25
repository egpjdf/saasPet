import { ref, computed } from 'vue';
import { useWorkspaceSettingsStore } from '@/Stores/workspaceSettings';
import type { WorkspaceLimit, LimitUpdateRequest } from '@/Types/workspaceSettings';
import { fetchWorkspaceLimits as apiFetch, updateLimit as apiUpdate, resetLimit as apiReset } from '@/Services/api/workspaceLimits';

export function useWorkspaceLimits() {
  const store = useWorkspaceSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);
  const updating = ref<Record<string, boolean>>({});

  const limits = computed(() => store.limits);
  const isLoaded = computed(() => store.limitsLoaded);

  const limitsWithValidation = computed(() => limits.value.map(limit => ({
    ...limit,
    minValue: limit.base_plan_limit,
    maxValue: limit.is_unlimited ? 999999999 : limit.base_plan_limit * 10,
    isValid: limit.new_value === null || (limit.new_value >= limit.base_plan_limit),
    validationMessage: limit.new_value !== null && limit.new_value < limit.base_plan_limit 
      ? `Valor mínimo: ${limit.base_plan_limit} ${limit.unit}` 
      : '',
  })));

  const hasPendingChanges = computed(() => 
    limits.value.some(l => l.new_value !== null && l.new_value !== l.current_override)
  );

  async function load(): Promise<WorkspaceLimit[]> {
    if (isLoaded.value) return store.limits;
    
    loading.value = true;
    error.value = null;
    
    try {
      const data = await apiFetch();
      store.setLimits(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar limites';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function update(request: LimitUpdateRequest): Promise<WorkspaceLimit> {
    if (request.new_value < 0) {
      throw new Error('Valor não pode ser negativo');
    }
    
    const limit = limits.value.find(l => l.key === request.limit_key);
    if (limit && request.new_value < limit.base_plan_limit) {
      throw new Error(`Valor mínimo: ${limit.base_plan_limit} ${limit.unit}`);
    }

    updating.value[request.limit_key] = true;
    error.value = null;
    
    try {
      const data = await apiUpdate(request);
      store.updateLimit(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar limite';
      throw err;
    } finally {
      updating.value[request.limit_key] = false;
    }
  }

  async function reset(limitKey: string): Promise<WorkspaceLimit> {
    updating.value[limitKey] = true;
    error.value = null;
    
    try {
      const data = await apiReset(limitKey);
      store.updateLimit(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao resetar limite';
      throw err;
    } finally {
      updating.value[limitKey] = false;
    }
  }

  function setPendingValue(limitKey: string, value: number | null) {
    store.setPendingLimitValue(limitKey, value);
  }

  function getPendingValue(limitKey: string): number | null {
    const limit = limits.value.find(l => l.key === limitKey);
    return limit?.new_value ?? null;
  }

  function isUpdating(limitKey: string): boolean {
    return !!updating.value[limitKey];
  }

  function clearError() {
    error.value = null;
  }

  return {
    limits,
    limitsWithValidation,
    hasPendingChanges,
    loading,
    error,
    updating,
    load,
    update,
    reset,
    setPendingValue,
    getPendingValue,
    isUpdating,
    clearError,
  };
}