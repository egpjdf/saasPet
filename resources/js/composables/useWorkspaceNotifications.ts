import { ref, computed, watch } from 'vue';
import { useWorkspaceSettingsStore } from '@/Stores/workspaceSettings';
import type { 
  NotificationSettings, 
  NotificationMatrixUpdateRequest, 
  DigestFrequencyUpdateRequest,
  NotificationChannel,
  NotificationCategory,
  NotificationMatrixCell 
} from '@/Types/workspaceSettings';
import { 
  fetchNotificationSettings as apiFetch, 
  updateNotificationMatrix as apiUpdateMatrix, 
  updateDigestFrequency as apiUpdateDigest,
  fetchNotificationChannels as apiChannels,
  fetchNotificationCategories as apiCategories
} from '@/Services/api/workspaceNotifications';

export function useWorkspaceNotifications() {
  const store = useWorkspaceSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);
  const saving = ref(false);
  const debounceTimer = ref<ReturnType<typeof setTimeout> | null>(null);

  const settings = computed(() => store.notifications);
  const channels = computed(() => store.notificationChannels);
  const categories = computed(() => store.notificationCategories);
  const matrix = computed(() => store.notificationMatrix);
  const digestFrequency = computed(() => store.digestFrequency);

  const matrixMap = computed(() => {
    const map: Record<string, Record<string, boolean>> = {};
    matrix.value.forEach(cell => {
      if (!map[cell.channel_key]) map[cell.channel_key] = {};
      map[cell.channel_key][cell.category_key] = cell.enabled;
    });
    return map;
  });

  async function load(): Promise<NotificationSettings> {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await apiFetch();
      store.setNotifications(data);
      await loadChannels();
      await loadCategories();
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar notificações';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function loadChannels(): Promise<NotificationChannel[]> {
    try {
      const data = await apiChannels();
      store.setNotificationChannels(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar canais';
      throw err;
    }
  }

  async function loadCategories(): Promise<NotificationCategory[]> {
    try {
      const data = await apiCategories();
      store.setNotificationCategories(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar categorias';
      throw err;
    }
  }

  async function updateMatrix(request: NotificationMatrixUpdateRequest): Promise<NotificationSettings> {
    saving.value = true;
    error.value = null;
    
    try {
      const data = await apiUpdateMatrix(request);
      store.updateNotificationMatrix(request.channel_key, request.category_key, request.enabled);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar matriz de notificações';
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function updateMatrixDebounced(request: NotificationMatrixUpdateRequest): void {
    store.updateNotificationMatrix(request.channel_key, request.category_key, request.enabled);
    
    if (debounceTimer.value) {
      clearTimeout(debounceTimer.value);
    }
    
    debounceTimer.value = setTimeout(() => {
      updateMatrix(request).catch(() => {});
    }, 500);
  }

  async function updateDigest(request: DigestFrequencyUpdateRequest): Promise<NotificationSettings> {
    saving.value = true;
    error.value = null;
    
    try {
      const data = await apiUpdateDigest(request);
      store.setDigestFrequency(request.frequency);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar frequência de digest';
      throw err;
    } finally {
      saving.value = false;
    }
  }

  function getMatrixValue(channelKey: string, categoryKey: string): boolean {
    return matrixMap.value[channelKey]?.[categoryKey] ?? false;
  }

  function setMatrixValue(channelKey: string, categoryKey: string, value: boolean): void {
    updateMatrixDebounced({ channel_key: channelKey, category_key: categoryKey, enabled: value });
  }

  function toggleMatrixValue(channelKey: string, categoryKey: string): void {
    const current = getMatrixValue(channelKey, categoryKey);
    setMatrixValue(channelKey, categoryKey, !current);
  }

  function clearError() {
    error.value = null;
  }

  return {
    settings,
    channels,
    categories,
    matrix,
    matrixMap,
    digestFrequency,
    loading,
    error,
    saving,
    load,
    loadChannels,
    loadCategories,
    updateMatrix,
    updateMatrixDebounced,
    updateDigest,
    getMatrixValue,
    setMatrixValue,
    toggleMatrixValue,
    clearError,
  };
}