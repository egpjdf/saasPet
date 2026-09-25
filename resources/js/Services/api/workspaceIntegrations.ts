import axios from 'axios';
import type { 
  WorkspaceIntegration, 
  IntegrationCreateRequest, 
  IntegrationUpdateRequest, 
  IntegrationTestRequest,
  IntegrationTestResponse,
  ApiResponse,
  PaginatedResponse 
} from '@/Types/workspaceSettings';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const tenantStore = useTenantStore();
  if (tenantStore.organization?.id) {
    config.headers['X-Organization-ID'] = tenantStore.organization.id;
  }
  if (tenantStore.workspace?.id) {
    config.headers['X-Workspace-ID'] = tenantStore.workspace.id;
  }
  return config;
});

function useTenantStore() {
  return (window as any).__PINIA__?.stores?.tenant || { organization: null, workspace: null };
}

export async function fetchWorkspaceIntegrations(): Promise<WorkspaceIntegration[]> {
  const response = await api.get<ApiResponse<WorkspaceIntegration[]>>('/workspace/integrations');
  return response.data.data;
}

export async function fetchIntegration(integrationId: string): Promise<WorkspaceIntegration> {
  const response = await api.get<ApiResponse<WorkspaceIntegration>>(`/workspace/integrations/${integrationId}`);
  return response.data.data;
}

export async function createIntegration(request: IntegrationCreateRequest): Promise<WorkspaceIntegration> {
  const response = await api.post<ApiResponse<WorkspaceIntegration>>('/workspace/integrations', request);
  return response.data.data;
}

export async function updateIntegration(integrationId: string, request: IntegrationUpdateRequest): Promise<WorkspaceIntegration> {
  const response = await api.put<ApiResponse<WorkspaceIntegration>>(`/workspace/integrations/${integrationId}`, request);
  return response.data.data;
}

export async function deleteIntegration(integrationId: string): Promise<void> {
  await api.delete(`/workspace/integrations/${integrationId}`);
}

export async function testIntegration(integrationId: string, request: IntegrationTestRequest): Promise<IntegrationTestResponse> {
  const response = await api.post<ApiResponse<IntegrationTestResponse>>(`/workspace/integrations/${integrationId}/test`, request);
  return response.data.data;
}

export async function testIntegrationConfig(type: string, request: IntegrationTestRequest): Promise<IntegrationTestResponse> {
  const response = await api.post<ApiResponse<IntegrationTestResponse>>(`/workspace/integrations/test/${type}`, request);
  return response.data.data;
}

export async function fetchIntegrationTypes(): Promise<{ type: string; name: string; description: string; config_schema: Record<string, any> }[]> {
  const response = await api.get<ApiResponse<any[]>>('/workspace/integrations/types');
  return response.data.data;
}