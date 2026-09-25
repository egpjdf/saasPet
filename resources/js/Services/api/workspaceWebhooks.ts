import axios from 'axios';
import type { 
  WorkspaceWebhook, 
  WebhookCreateRequest, 
  WebhookUpdateRequest, 
  WebhookTestRequest,
  WebhookTestResponse,
  WebhookEvent,
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

export async function fetchWorkspaceWebhooks(): Promise<WorkspaceWebhook[]> {
  const response = await api.get<ApiResponse<WorkspaceWebhook[]>>('/workspace/webhooks');
  return response.data.data;
}

export async function fetchWebhook(webhookId: string): Promise<WorkspaceWebhook> {
  const response = await api.get<ApiResponse<WorkspaceWebhook>>(`/workspace/webhooks/${webhookId}`);
  return response.data.data;
}

export async function createWebhook(request: WebhookCreateRequest): Promise<WorkspaceWebhook> {
  const response = await api.post<ApiResponse<WorkspaceWebhook>>('/workspace/webhooks', request);
  return response.data.data;
}

export async function updateWebhook(webhookId: string, request: WebhookUpdateRequest): Promise<WorkspaceWebhook> {
  const response = await api.put<ApiResponse<WorkspaceWebhook>>(`/workspace/webhooks/${webhookId}`, request);
  return response.data.data;
}

export async function deleteWebhook(webhookId: string): Promise<void> {
  await api.delete(`/workspace/webhooks/${webhookId}`);
}

export async function testWebhook(webhookId: string, request?: WebhookTestRequest): Promise<WebhookTestResponse> {
  const response = await api.post<ApiResponse<WebhookTestResponse>>(`/workspace/webhooks/${webhookId}/test`, request || {});
  return response.data.data;
}

export async function testWebhookConfig(request: WebhookTestRequest): Promise<WebhookTestResponse> {
  const response = await api.post<ApiResponse<WebhookTestResponse>>('/workspace/webhooks/test', request);
  return response.data.data;
}

export async function fetchWebhookEvents(): Promise<WebhookEvent[]> {
  const response = await api.get<ApiResponse<WebhookEvent[]>>('/workspace/webhooks/events');
  return response.data.data;
}

export async function regenerateHmacSecret(webhookId: string): Promise<{ hmac_secret: string }> {
  const response = await api.post<ApiResponse<{ hmac_secret: string }>>(`/workspace/webhooks/${webhookId}/regenerate-secret`);
  return response.data.data;
}