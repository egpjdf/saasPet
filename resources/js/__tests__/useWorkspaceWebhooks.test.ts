import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useWorkspaceWebhooks } from '@/Composables/useWorkspaceWebhooks';

// Mock the store
vi.mock('@/Stores/workspaceSettings', () => ({
  useWorkspaceSettingsStore: () => ({
    webhooks: [],
    webhookEvents: [],
    setWebhooks: vi.fn(),
    addWebhook: vi.fn(),
    updateWebhook: vi.fn(),
    removeWebhook: vi.fn(),
    updateWebhookHmacSecret: vi.fn(),
    setWebhookEvents: vi.fn(),
  }),
}));

// Mock the API
vi.mock('@/Services/api/workspaceWebhooks', () => ({
  fetchWorkspaceWebhooks: vi.fn(),
  fetchWebhook: vi.fn(),
  createWebhook: vi.fn(),
  updateWebhook: vi.fn(),
  deleteWebhook: vi.fn(),
  testWebhook: vi.fn(),
  testWebhookConfig: vi.fn(),
  fetchWebhookEvents: vi.fn(),
  regenerateHmacSecret: vi.fn(),
}));

import { 
  fetchWorkspaceWebhooks, 
  createWebhook, 
  updateWebhook, 
  deleteWebhook, 
  testWebhook,
  testWebhookConfig,
  fetchWebhookEvents,
  regenerateHmacSecret 
} from '@/Services/api/workspaceWebhooks';

describe('useWorkspaceWebhooks', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should load webhooks', async () => {
    const mockWebhooks = [
      { id: '1', url: 'https://example.com/webhook', events: ['order.created'], status: 'active', hmac_secret: 'secret1', created_at: '', updated_at: '' },
      { id: '2', url: 'https://example.com/webhook2', events: ['order.updated'], status: 'inactive', hmac_secret: 'secret2', created_at: '', updated_at: '' },
    ];
    
    (fetchWorkspaceWebhooks as any).mockResolvedValue(mockWebhooks);
    (fetchWebhookEvents as any).mockResolvedValue([]);

    const { load, webhooks, webhookEvents } = useWorkspaceWebhooks();
    await load();

    expect(fetchWorkspaceWebhooks).toHaveBeenCalled();
    expect(fetchWebhookEvents).toHaveBeenCalled();
    expect(webhooks.value).toEqual(mockWebhooks);
  });

  it('should create webhook', async () => {
    const newWebhook = { id: '3', url: 'https://example.com/webhook3', events: ['order.deleted'], status: 'active', hmac_secret: 'secret3', created_at: '', updated_at: '' };
    (createWebhook as any).mockResolvedValue(newWebhook);

    const { create, webhooks } = useWorkspaceWebhooks();
    (webhooks.value as any) = [];
    
    const result = await create({ url: 'https://example.com/webhook3', events: ['order.deleted'] });

    expect(createWebhook).toHaveBeenCalledWith({ url: 'https://example.com/webhook3', events: ['order.deleted'] });
    expect(result).toEqual(newWebhook);
  });

  it('should update webhook', async () => {
    const updatedWebhook = { id: '1', url: 'https://example.com/webhook', events: ['order.created', 'order.updated'], status: 'active', hmac_secret: 'secret1', created_at: '', updated_at: '' };
    (updateWebhook as any).mockResolvedValue(updatedWebhook);

    const { update, webhooks } = useWorkspaceWebhooks();
    (webhooks.value as any) = [{ id: '1', url: 'https://example.com/webhook', events: ['order.created'], status: 'active', hmac_secret: 'secret1', created_at: '', updated_at: '' }];
    
    const result = await update('1', { events: ['order.created', 'order.updated'] });

    expect(updateWebhook).toHaveBeenCalledWith('1', { events: ['order.created', 'order.updated'] });
    expect(result).toEqual(updatedWebhook);
  });

  it('should delete webhook', async () => {
    (deleteWebhook as any).mockResolvedValue(undefined);

    const { remove, webhooks } = useWorkspaceWebhooks();
    (webhooks.value as any) = [
      { id: '1', url: 'https://example.com/webhook', events: ['order.created'], status: 'active', hmac_secret: 'secret1', created_at: '', updated_at: '' },
      { id: '2', url: 'https://example.com/webhook2', events: ['order.updated'], status: 'inactive', hmac_secret: 'secret2', created_at: '', updated_at: '' },
    ];
    
    await remove('1');

    expect(deleteWebhook).toHaveBeenCalledWith('1');
    expect(webhooks.value).toHaveLength(1);
    expect(webhooks.value[0].id).toBe('2');
  });

  it('should test webhook', async () => {
    const testResult = { success: true, status_code: 200, response_body: 'OK', response_time_ms: 150 };
    (testWebhook as any).mockResolvedValue(testResult);

    const { test, webhooks } = useWorkspaceWebhooks();
    (webhooks.value as any) = [{ id: '1', url: 'https://example.com/webhook', events: ['order.created'], status: 'active', hmac_secret: 'secret1', created_at: '', updated_at: '' }];
    
    const result = await test('1');

    expect(testWebhook).toHaveBeenCalledWith('1', {});
    expect(result).toEqual(testResult);
  });

  it('should test webhook config without saving', async () => {
    const testResult = { success: true, status_code: 200, response_body: 'OK', response_time_ms: 100 };
    (testWebhookConfig as any).mockResolvedValue(testResult);

    const { testConfig } = useWorkspaceWebhooks();
    
    const result = await testConfig({ url: 'https://example.com/test', events: ['order.created'] });

    expect(testWebhookConfig).toHaveBeenCalledWith({ url: 'https://example.com/test', events: ['order.created'] });
    expect(result).toEqual(testResult);
  });

  it('should regenerate HMAC secret', async () => {
    const newSecret = 'new-hmac-secret-123';
    (regenerateHmacSecret as any).mockResolvedValue({ hmac_secret: newSecret });

    const { regenerateSecret, webhooks } = useWorkspaceWebhooks();
    (webhooks.value as any) = [{ id: '1', url: 'https://example.com/webhook', events: ['order.created'], status: 'active', hmac_secret: 'old-secret', created_at: '', updated_at: '' }];
    
    const result = await regenerateSecret('1');

    expect(regenerateHmacSecret).toHaveBeenCalledWith('1');
    expect(result).toBe(newSecret);
    expect(webhooks.value[0].hmac_secret).toBe(newSecret);
  });

  it('should filter webhooks by status', () => {
    const { webhooks, activeWebhooks, inactiveWebhooks, failedWebhooks } = useWorkspaceWebhooks();
    
    (webhooks.value as any) = [
      { id: '1', status: 'active' },
      { id: '2', status: 'inactive' },
      { id: '3', status: 'failed' },
      { id: '4', status: 'active' },
    ];

    expect(activeWebhooks.value).toHaveLength(2);
    expect(inactiveWebhooks.value).toHaveLength(1);
    expect(failedWebhooks.value).toHaveLength(1);
  });

  it('should group events by category', () => {
    const { webhookEvents, eventsByCategory } = useWorkspaceWebhooks();
    
    (webhookEvents.value as any) = [
      { key: 'order.created', name: 'Order Created', category: 'orders', description: '' },
      { key: 'order.updated', name: 'Order Updated', category: 'orders', description: '' },
      { key: 'customer.created', name: 'Customer Created', category: 'customers', description: '' },
    ];

    expect(eventsByCategory.value.orders).toHaveLength(2);
    expect(eventsByCategory.value.customers).toHaveLength(1);
  });
});