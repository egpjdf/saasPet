import { ref, computed } from 'vue';
import { useWorkspaceSettingsStore } from '@/Stores/workspaceSettings';
import type { WorkspaceSettings } from '@/Types/workspaceSettings';
import { fetchWorkspaceSettings as apiFetch, updateWorkspaceSettings as apiUpdate } from '@/Services/api/workspaceSettings';

export function useWorkspaceSettings() {
  const store = useWorkspaceSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);

  const settings = computed(() => store.settings);
  const isLoaded = computed(() => store.isLoaded);

  async function load(): Promise<WorkspaceSettings | null> {
    if (isLoaded.value) return store.settings;
    
    loading.value = true;
    error.value = null;
    
    try {
      const data = await apiFetch();
      store.setSettings(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar configurações';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function update(settingsData: Partial<WorkspaceSettings>): Promise<WorkspaceSettings> {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await apiUpdate(settingsData);
      store.setSettings(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar configurações';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function clearError() {
    error.value = null;
  }

  return {
    settings,
    loading,
    error,
    isLoaded,
    load,
    update,
    clearError,
  };
}