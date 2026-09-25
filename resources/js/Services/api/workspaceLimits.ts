import axios from 'axios';
import type { WorkspaceLimit, LimitUpdateRequest, ApiResponse } from '@/Types/workspaceSettings';

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

export async function fetchWorkspaceLimits(): Promise<WorkspaceLimit[]> {
  const response = await api.get<ApiResponse<WorkspaceLimit[]>>('/workspace/limits');
  return response.data.data;
}

export async function updateLimit(request: LimitUpdateRequest): Promise<WorkspaceLimit> {
  const response = await api.put<ApiResponse<WorkspaceLimit>>(`/workspace/limits/${request.limit_key}`, request);
  return response.data.data;
}

export async function resetLimit(limitKey: string): Promise<WorkspaceLimit> {
  const response = await api.delete<ApiResponse<WorkspaceLimit>>(`/workspace/limits/${limitKey}`);
  return response.data.data;
}