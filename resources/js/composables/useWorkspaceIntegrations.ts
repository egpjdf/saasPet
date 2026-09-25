import { ref, computed } from 'vue';
import { useWorkspaceSettingsStore } from '@/Stores/workspaceSettings';
import type { 
  WorkspaceIntegration, 
  IntegrationCreateRequest, 
  IntegrationUpdateRequest, 
  IntegrationTestRequest,
  IntegrationTestResponse 
} from '@/Types/workspaceSettings';
import { 
  fetchWorkspaceIntegrations as apiFetch, 
  fetchIntegration as apiFetchOne,
  createIntegration as apiCreate, 
  updateIntegration as apiUpdate, 
  deleteIntegration as apiDelete, 
  testIntegration as apiTest,
  testIntegrationConfig as apiTestConfig,
  fetchIntegrationTypes as apiTypes
} from '@/Services/api/workspaceIntegrations';

export function useWorkspaceIntegrations() {
  const store = useWorkspaceSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);
  const testing = ref<Record<string, boolean>>({});
  const creating = ref(false);
  const updating = ref<Record<string, boolean>>({});
  const deleting = ref<Record<string, boolean>>({});

  const integrations = computed(() => store.integrations);
  const integrationTypes = computed(() => store.integrationTypes);

  const connectedIntegrations = computed(() => integrations.value.filter(i => i.status === 'connected'));
  const disconnectedIntegrations = computed(() => integrations.value.filter(i => i.status === 'disconnected'));
  const errorIntegrations = computed(() => integrations.value.filter(i => i.status === 'error'));

  async function load(): Promise<WorkspaceIntegration[]> {
    loading.value = true;
    error.value = null;
    
    try {
      const data = await apiFetch();
      store.setIntegrations(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar integrações';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function loadTypes(): Promise<void> {
    try {
      const data = await apiTypes();
      store.setIntegrationTypes(data);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar tipos de integração';
    }
  }

  async function get(integrationId: string): Promise<WorkspaceIntegration> {
    try {
      return await apiFetchOne(integrationId);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao carregar integração';
      throw err;
    }
  }

  async function create(request: IntegrationCreateRequest): Promise<WorkspaceIntegration> {
    creating.value = true;
    error.value = null;
    
    try {
      const data = await apiCreate(request);
      store.addIntegration(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao criar integração';
      throw err;
    } finally {
      creating.value = false;
    }
  }

  async function update(integrationId: string, request: IntegrationUpdateRequest): Promise<WorkspaceIntegration> {
    updating.value[integrationId] = true;
    error.value = null;
    
    try {
      const data = await apiUpdate(integrationId, request);
      store.updateIntegration(data);
      return data;
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao atualizar integração';
      throw err;
    } finally {
      updating.value[integrationId] = false;
    }
  }

  async function remove(integrationId: string): Promise<void> {
    deleting.value[integrationId] = true;
    error.value = null;
    
    try {
      await apiDelete(integrationId);
      store.removeIntegration(integrationId);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao remover integração';
      throw err;
    } finally {
      deleting.value[integrationId] = false;
    }
  }

  async function test(integrationId: string, request: IntegrationTestRequest): Promise<IntegrationTestResponse> {
    testing.value[integrationId] = true;
    error.value = null;
    
    try {
      return await apiTest(integrationId, request);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao testar integração';
      throw err;
    } finally {
      testing.value[integrationId] = false;
    }
  }

  async function testConfig(type: string, request: IntegrationTestRequest): Promise<IntegrationTestResponse> {
    error.value = null;
    
    try {
      return await apiTestConfig(type, request);
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Erro ao testar configuração';
      throw err;
    }
  }

  function isTesting(integrationId: string): boolean {
    return !!testing.value[integrationId];
  }

  function isCreating(): boolean {
    return creating.value;
  }

  function isUpdating(integrationId: string): boolean {
    return !!updating.value[integrationId];
  }

  function isDeleting(integrationId: string): boolean {
    return !!deleting.value[integrationId];
  }

  function clearError() {
    error.value = null;
  }

  return {
    integrations,
    integrationTypes,
    connectedIntegrations,
    disconnectedIntegrations,
    errorIntegrations,
    loading,
    error,
    testing,
    creating,
    updating,
    deleting,
    load,
    loadTypes,
    get,
    create,
    update,
    remove,
    test,
    testConfig,
    isTesting,
    isCreating,
    isUpdating,
    isDeleting,
    clearError,
  };
}