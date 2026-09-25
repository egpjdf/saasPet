import axios from 'axios';
import type { WorkspaceSettings, ApiResponse } from '@/Types/workspaceSettings';

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

export async function fetchWorkspaceSettings(): Promise<WorkspaceSettings> {
  const response = await api.get<ApiResponse<WorkspaceSettings>>('/workspace/settings');
  return response.data.data;
}

export async function updateWorkspaceSettings(settings: Partial<WorkspaceSettings>): Promise<WorkspaceSettings> {
  const response = await api.put<ApiResponse<WorkspaceSettings>>('/workspace/settings', settings);
  return response.data.data;
}