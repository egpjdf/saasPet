import axios from 'axios';
import type { 
  NotificationSettings, 
  NotificationMatrixUpdateRequest, 
  DigestFrequencyUpdateRequest,
  ApiResponse 
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

export async function fetchNotificationSettings(): Promise<NotificationSettings> {
  const response = await api.get<ApiResponse<NotificationSettings>>('/workspace/notifications');
  return response.data.data;
}

export async function updateNotificationMatrix(request: NotificationMatrixUpdateRequest): Promise<NotificationSettings> {
  const response = await api.put<ApiResponse<NotificationSettings>>('/workspace/notifications/matrix', request);
  return response.data.data;
}

export async function updateDigestFrequency(request: DigestFrequencyUpdateRequest): Promise<NotificationSettings> {
  const response = await api.put<ApiResponse<NotificationSettings>>('/workspace/notifications/digest', request);
  return response.data.data;
}

export async function fetchNotificationChannels(): Promise<{ key: string; name: string; description: string }[]> {
  const response = await api.get<ApiResponse<any[]>>('/workspace/notifications/channels');
  return response.data.data;
}

export async function fetchNotificationCategories(): Promise<{ key: string; name: string; description: string }[]> {
  const response = await api.get<ApiResponse<any[]>>('/workspace/notifications/categories');
  return response.data.data;
}