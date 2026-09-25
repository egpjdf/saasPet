import { ref, computed } from 'vue';
import { useWorkspaceSettingsStore } from '@/Stores/workspaceSettings';
import type { 
  WorkspaceWebhook, 
  WebhookCreateRequest, 
  WebhookUpdateRequest, 
  WebhookTestRequest,
  WebhookTestResponse,
  WebhookEvent 
} from '@/Types/workspaceSettings';
import { 
  fetchWorkspaceWebhooks as apiFetch, 
  fetchWebhook as apiFetchOne,
  createWebhook as apiCreate, 
  updateWebhook as apiUpdate, 
  deleteWebhook as apiDelete, 
  testWebhook as apiTest,
  testWebhookConfig as apiTestConfig,
  fetchWebhookEvents as apiEvents,
  regenerateHmacSecret as apiRegenerateSecret
} from '@/Services/api/workspaceWebhooks';

export function useWorkspaceWebhooks() {
  const store = useWorkspaceSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);
  const testing = ref<Record<string, boolean>>({});
  const creating = ref(false);
  const updating = ref<Record<string, boolean>>({});
  const deleting = ref<Record<string, boolean>>({});
  const regeneratingSecret = ref<Record<string, boolean>>({});

  const webhooks = computed(() => store.webhooks);
  const webhookEvents = computed(() => store.webhookEvents);

  const activeWebhooks = computed(() => webhooks.value.filter(w => w.status === 'active'));
  const inactiveWebhooks = computed(() => webhooks.value.filter(w => w.status === 'inactive'));
  const failedWebhooks = computed(() => webhooks.value.filter(w => w.status === 'failed'));

  const eventsByCategory = computed(() => {
    const result: Record<string, WebhookEvent[]> = {};
    webhookEvents.value.forEach(event => {
      if (!result[event.category]) result[event.category] = [];
      result[event.category].push(event);
    });
    return result;
  });

  async function load(): Promise<WorkspaceWebhook[]> {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await apiFetch();
      store.setWebhooks(data);
      await loadEvents();
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar webhooks';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function loadEvents(): Promise<WebhookEvent[]> {
    try {
      const data = await apiEvents();
      store.setWebhookEvents(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar eventos de webhook';
      throw err;
    }
  }

  async function get(webhookId: string): Promise<WorkspaceWebhook> {
    try {
      return await apiFetchOne(webhookId);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar webhook';
      throw err;
    }
  }

  async function create(request: WebhookCreateRequest): Promise<WorkspaceWebhook> {
    creating.value = true;
    error.value = null;
    
    try {
      const data = await apiCreate(request);
      store.addWebhook(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao criar webhook';
      throw err;
    } finally {
      creating.value = false;
    }
  }

  async function update(webhookId: string, request: WebhookUpdateRequest): Promise<WorkspaceWebhook> {
    updating.value[webhookId] = true;
    error.value = null;
    
    try {
      const data = await apiUpdate(webhookId, request);
      store.updateWebhook(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar webhook';
      throw err;
    } finally {
      updating.value[webhookId] = false;
    }
  }

  async function remove(webhookId: string): Promise<void> {
    deleting.value[webhookId] = true;
    error.value = null;
    
    try {
      await apiDelete(webhookId);
      store.removeWebhook(webhookId);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao remover webhook';
      throw err;
    } finally {
      deleting.value[webhookId] = false;
    }
  }

  async function test(webhookId: string, request?: WebhookTestRequest): Promise<WebhookTestResponse> {
    testing.value[webhookId] = true;
    error.value = null;
    
    try {
      return await apiTest(webhookId, request);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao testar webhook';
      throw err;
    } finally {
      testing.value[webhookId] = false;
    }
  }

  async function testConfig(request: WebhookTestRequest): Promise<WebhookTestResponse> {
    error.value = null;
    
    try {
      return await apiTestConfig(request);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao testar configuração de webhook';
      throw err;
    }
  }

  async function regenerateSecret(webhookId: string): Promise<string> {
    regeneratingSecret.value[webhookId] = true;
    error.value = null;
    
    try {
      const data = await apiRegenerateSecret(webhookId);
      store.updateWebhookHmacSecret(webhookId, data.hmac_secret);
      return data.hmac_secret;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao regenerar segredo HMAC';
      throw err;
    } finally {
      regeneratingSecret.value[webhookId] = false;
    }
  }

  function isTesting(webhookId: string): boolean {
    return !!testing.value[webhookId];
  }

  function isCreating(): boolean {
    return creating.value;
  }

  function isUpdating(webhookId: string): boolean {
    return !!updating.value[webhookId];
  }

  function isDeleting(webhookId: string): boolean {
    return !!deleting.value[webhookId];
  }

  function isRegeneratingSecret(webhookId: string): boolean {
    return !!regeneratingSecret.value[webhookId];
  }

  function clearError() {
    error.value = null;
  }

  return {
    webhooks,
    webhookEvents,
    eventsByCategory,
    activeWebhooks,
    inactiveWebhooks,
    failedWebhooks,
    loading,
    error,
    testing,
    creating,
    updating,
    deleting,
    regeneratingSecret,
    load,
    loadEvents,
    get,
    create,
    update,
    remove,
    test,
    testConfig,
    regenerateSecret,
    isTesting,
    isCreating,
    isUpdating,
    isDeleting,
    isRegeneratingSecret,
    clearError,
  };
}