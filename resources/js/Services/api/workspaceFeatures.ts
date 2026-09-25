import axios from 'axios';
import type { WorkspaceFeature, FeatureToggleRequest, ApiResponse } from '@/Types/workspaceSettings';

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

export async function fetchWorkspaceFeatures(): Promise<WorkspaceFeature[]> {
  const response = await api.get<ApiResponse<WorkspaceFeature[]>>('/workspace/features');
  return response.data.data;
}

export async function toggleFeature(request: FeatureToggleRequest): Promise<WorkspaceFeature> {
  const response = await api.post<ApiResponse<WorkspaceFeature>>('/workspace/features/toggle', request);
  return response.data.data;
}

export async function fetchAvailableFeatures(): Promise<WorkspaceFeature[]> {
  const response = await api.get<ApiResponse<WorkspaceFeature[]>>('/workspace/features/available');
  return response.data.data;
}